<?php

namespace App\Services\Fiscal;

use App\Enums\Fiscal\FiscalAmbiente;
use App\Enums\Fiscal\FiscalLogTipo;
use App\Enums\Fiscal\FiscalModoEmissao;
use App\Enums\Fiscal\FiscalNotaStatus;
use App\Enums\Fiscal\FiscalTipoDocumento;
use App\Models\FiscalConfiguracao;
use App\Models\FiscalEmitente;
use App\Models\FiscalLog;
use App\Models\FiscalNota;
use App\Models\Pedido;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Orquestra emissão fiscal sem acoplar controllers às APIs externas.
 * Regras de pedido e UI devem depender apenas desta fachada + models {@see FiscalNota}.
 */
class FiscalService
{
    public function __construct(
        private readonly FiscalEmissorRegistry $registry,
    ) {}

    public function registrarLog(
        int $empresaId,
        FiscalLogTipo $tipo,
        string $mensagem,
        ?int $notaId = null,
        ?array $payload = null,
        ?array $retorno = null,
        ?int $unidadeId = null,
    ): FiscalLog {
        return FiscalLog::query()->create([
            'empresa_id' => $empresaId,
            'nota_id' => $notaId,
            'tipo' => $tipo,
            'mensagem' => $mensagem,
            'payload' => $payload,
            'retorno' => $retorno,
            'unidade_id' => $unidadeId,
        ]);
    }

    /** @return string|null Mensagem de erro ou null se OK. */
    public function validarPedidoParaEmissao(Pedido $pedido, FiscalConfiguracao $config): ?string
    {
        if (! $config->modulo_ativo) {
            return 'Módulo fiscal desativado nas configurações.';
        }
        if ($config->modo_emissao === FiscalModoEmissao::NaoEmitir) {
            return 'Modo de emissão configurado como «Não emitir».';
        }
        if ($pedido->fiscal_quer_cpf_nota) {
            $doc = preg_replace('/\D+/', '', (string) $pedido->fiscal_documento);
            if (strlen($doc) !== 11 && strlen($doc) !== 14) {
                return 'Informe CPF (11 dígitos) ou CNPJ (14 dígitos) válido na nota.';
            }
            if (trim((string) $pedido->fiscal_razao_social) === '') {
                return 'Informe nome ou razão social para a nota.';
            }
            if (trim((string) $pedido->fiscal_email) === '' || ! filter_var($pedido->fiscal_email, FILTER_VALIDATE_EMAIL)) {
                return 'Informe um e-mail válido para envio da nota.';
            }
        }

        return null;
    }

    public function emitentePadraoAtivo(int $empresaId): ?FiscalEmitente
    {
        return FiscalEmitente::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Monta/atualiza registro de nota, chama driver e persiste status (sem emissão real enquanto drivers forem placeholders).
     */
    public function iniciarEmissao(Pedido $pedido, FiscalConfiguracao $config): FiscalEmissaoResultado
    {
        $erro = $this->validarPedidoParaEmissao($pedido, $config);
        if ($erro !== null) {
            $this->registrarLog($pedido->empresa_id, FiscalLogTipo::Erro, $erro, null, ['pedido_id' => $pedido->id]);

            return new FiscalEmissaoResultado(false, $erro, ['pedido_id' => $pedido->id], []);
        }

        $emitente = $this->emitentePadraoAtivo($pedido->empresa_id);
        if ($emitente === null) {
            $msg = 'Cadastre pelo menos um emitente fiscal ativo (menu Empresas emitentes).';
            $this->registrarLog($pedido->empresa_id, FiscalLogTipo::Erro, $msg, null, ['pedido_id' => $pedido->id]);

            return new FiscalEmissaoResultado(false, $msg, ['pedido_id' => $pedido->id], []);
        }

        $driver = $this->registry->resolve($emitente, $config->emissor_driver_padrao);

        return DB::transaction(function () use ($pedido, $config, $emitente, $driver): FiscalEmissaoResultado {
            $nota = FiscalNota::query()->updateOrCreate(
                ['pedido_id' => $pedido->id],
                [
                    'empresa_id' => $pedido->empresa_id,
                    'tipo_nota' => $config->tipo_documento ?? FiscalTipoDocumento::Nfce,
                    'status' => FiscalNotaStatus::Processando,
                    'valor_total' => $pedido->total,
                    'ambiente' => $config->ambiente ?? FiscalAmbiente::Homologacao,
                    'payload_json' => $this->montarPayloadSimulado($pedido, $emitente, $config),
                ]
            );

            $this->registrarLog(
                $pedido->empresa_id,
                FiscalLogTipo::Emissao,
                'Início da emissão fiscal (pedido '.$pedido->codigo_publico.').',
                $nota->id,
                $nota->payload_json,
            );

            try {
                $resultado = $driver->emitir($nota, $nota->payload_json ?? []);
            } catch (Throwable $e) {
                $this->registrarLog(
                    $pedido->empresa_id,
                    FiscalLogTipo::Excecao,
                    $e->getMessage(),
                    $nota->id,
                    [],
                    ['exception' => $e::class],
                );
                $nota->update([
                    'status' => FiscalNotaStatus::Rejeitada,
                    'motivo_rejeicao' => 'Exceção interna: '.$e->getMessage(),
                    'retorno_json' => ['exception' => $e::class],
                ]);
                $nota->refresh();
                $this->sincronizarResumoPedido($nota);

                return new FiscalEmissaoResultado(false, 'Falha interna ao acionar emissor.', [], ['erro' => $e->getMessage()]);
            }

            $nota->update([
                'status' => $resultado->ok ? FiscalNotaStatus::Autorizada : FiscalNotaStatus::Rejeitada,
                'motivo_rejeicao' => $resultado->ok ? null : $resultado->mensagem,
                'retorno_json' => $resultado->retorno,
                'data_emissao' => $resultado->ok ? now() : null,
            ]);

            $this->registrarLog(
                $pedido->empresa_id,
                $resultado->ok ? FiscalLogTipo::Emissao : FiscalLogTipo::Erro,
                $resultado->mensagem,
                $nota->id,
                $resultado->payload,
                $resultado->retorno,
            );

            $nota->refresh();
            $this->sincronizarResumoPedido($nota);

            return $resultado;
        });
    }

    public function reemitir(Pedido $pedido, FiscalConfiguracao $config): FiscalEmissaoResultado
    {
        $nota = FiscalNota::query()->where('pedido_id', $pedido->id)->first();
        if ($nota === null) {
            return new FiscalEmissaoResultado(false, 'Não há registro de nota para este pedido.', [], []);
        }
        $st = $nota->status instanceof FiscalNotaStatus ? $nota->status : FiscalNotaStatus::tryFrom((string) $nota->status) ?? FiscalNotaStatus::NaoEmitida;
        if (! in_array($st, [FiscalNotaStatus::Rejeitada, FiscalNotaStatus::NaoEmitida, FiscalNotaStatus::Cancelada], true)) {
            return new FiscalEmissaoResultado(false, 'Só é possível reemitir quando a nota está rejeitada, não emitida ou cancelada.', [], []);
        }

        $nota->update(['status' => FiscalNotaStatus::AguardandoEmissao, 'motivo_rejeicao' => null]);
        $nota->refresh();
        $this->sincronizarResumoPedido($nota);

        return $this->iniciarEmissao($pedido, $config);
    }

    public function cancelarNota(Pedido $pedido, FiscalConfiguracao $config, string $motivo): FiscalEmissaoResultado
    {
        $nota = FiscalNota::query()->where('pedido_id', $pedido->id)->first();
        if ($nota === null) {
            return new FiscalEmissaoResultado(false, 'Não há nota para cancelar.', [], []);
        }

        $emitente = $this->emitentePadraoAtivo($pedido->empresa_id);
        $driver = $this->registry->resolve($emitente, $config->emissor_driver_padrao);

        try {
            $r = $driver->cancelar($nota, $motivo);
        } catch (Throwable $e) {
            $this->registrarLog($pedido->empresa_id, FiscalLogTipo::Excecao, $e->getMessage(), $nota->id);

            return new FiscalEmissaoResultado(false, $e->getMessage(), [], []);
        }

        $simulado = (bool) ($r->retorno['simulado'] ?? false);
        if ($r->ok || $simulado) {
            $nota->update(['status' => FiscalNotaStatus::Cancelada, 'motivo_rejeicao' => $motivo]);
        }
        $this->registrarLog($pedido->empresa_id, FiscalLogTipo::Cancelamento, $r->mensagem, $nota->id, ['motivo' => $motivo], $r->retorno);
        $nota->refresh();
        $this->sincronizarResumoPedido($nota);

        return $r;
    }

    public function sincronizarResumoPedido(FiscalNota $nota): void
    {
        $pedido = $nota->pedido;
        if ($pedido === null) {
            return;
        }

        $status = $nota->status instanceof FiscalNotaStatus ? $nota->status : FiscalNotaStatus::from((string) $nota->status);

        $fiscalPedido = match ($status) {
            FiscalNotaStatus::Autorizada => Pedido::FISCAL_NOTA_AUTORIZADA,
            FiscalNotaStatus::Rejeitada => Pedido::FISCAL_NOTA_REJEITADA,
            FiscalNotaStatus::AguardandoEmissao, FiscalNotaStatus::Processando, FiscalNotaStatus::Contingencia => Pedido::FISCAL_AGUARDANDO_EMISSAO,
            FiscalNotaStatus::Cancelada, FiscalNotaStatus::NaoEmitida => Pedido::FISCAL_SEM_NOTA,
        };

        $pedido->update(['fiscal_status' => $fiscalPedido]);
    }

    /** @return array<string, mixed> */
    private function montarPayloadSimulado(Pedido $pedido, FiscalEmitente $emitente, FiscalConfiguracao $config): array
    {
        return [
            'pedido_codigo' => $pedido->codigo_publico,
            'valor_total' => (string) $pedido->total,
            'emitente_cnpj' => preg_replace('/\D+/', '', $emitente->cnpj),
            'tipo_documento' => $config->tipo_documento?->value ?? FiscalTipoDocumento::Nfce->value,
            'ambiente' => $config->ambiente?->value ?? FiscalAmbiente::Homologacao->value,
            'destinatario' => [
                'com_documento' => (bool) $pedido->fiscal_quer_cpf_nota,
                'cpf_cnpj' => $pedido->fiscal_documento,
                'nome' => $pedido->fiscal_razao_social,
                'email' => $pedido->fiscal_email,
            ],
        ];
    }
}
