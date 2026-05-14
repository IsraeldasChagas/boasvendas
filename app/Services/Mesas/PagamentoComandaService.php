<?php

namespace App\Services\Mesas;

use App\Enums\Mesas\ComandaStatus;
use App\Enums\Mesas\PagamentoComandaStatus;
use App\Models\Comanda;
use App\Models\PagamentoComanda;
use Illuminate\Support\Facades\DB;

class PagamentoComandaService
{
    public function __construct(
        private readonly ComandaService $comandaService,
        private readonly MesaService $mesaService,
    ) {}

    /**
     * Aplica taxa/desconto, registra pagamentos como confirmados e encerra a comanda.
     * Gera payload JSON para integrações futuras (financeiro, estoque, fiscal).
     *
     * @param  list<array{forma_pagamento: string, valor_pago: float|int|string, troco?: float|int|string}>  $pagamentos
     */
    public function finalizarComanda(
        Comanda $comanda,
        ?float $taxaPercentual,
        float $desconto,
        array $pagamentos,
        ?int $usuarioId = null,
    ): void {
        if (! in_array($comanda->status, [ComandaStatus::EmConsumo, ComandaStatus::ContaSolicitada, ComandaStatus::Aberta], true)) {
            throw new \RuntimeException('Esta comanda não pode ser finalizada.');
        }

        if ($pagamentos === []) {
            throw new \InvalidArgumentException('Informe ao menos uma forma de pagamento.');
        }

        DB::transaction(function () use ($comanda, $taxaPercentual, $desconto, $pagamentos, $usuarioId) {
            $this->comandaService->aplicarTaxaDesconto($comanda, $taxaPercentual, $desconto);
            $comanda->refresh();

            $totalEsperado = round((float) $comanda->total, 2);
            $soma = 0.0;
            foreach ($pagamentos as $row) {
                $soma += round((float) ($row['valor_pago'] ?? 0), 2);
            }

            if (abs($soma - $totalEsperado) > 0.02) {
                throw new \InvalidArgumentException(
                    'A soma dos pagamentos (R$ '.number_format($soma, 2, ',', '.').
                    ') deve fechar com o total da comanda (R$ '.number_format($totalEsperado, 2, ',', '.').').'
                );
            }

            $comanda->pagamentos()->delete();

            foreach ($pagamentos as $row) {
                $p = new PagamentoComanda;
                $p->comanda_id = $comanda->id;
                $p->forma_pagamento = (string) $row['forma_pagamento'];
                $p->valor_pago = number_format((float) $row['valor_pago'], 2, '.', '');
                $p->troco = number_format((float) ($row['troco'] ?? 0), 2, '.', '');
                $p->status = PagamentoComandaStatus::Confirmado;
                $p->save();
            }

            $comanda->status = ComandaStatus::Fechada;
            $comanda->fechada_em = now();
            $comanda->integracao_payload = $this->montarPayloadIntegracao($comanda, $usuarioId);
            $comanda->save();

            $this->mesaService->liberarMesaAposFechamento($comanda);
        });
    }

    /** @return array<string, mixed> */
    private function montarPayloadIntegracao(Comanda $comanda, ?int $usuarioId): array
    {
        $comanda->load(['itens', 'pagamentos', 'mesa']);

        return [
            'versao' => 1,
            'origem' => 'vendas_mesa',
            'comanda_id' => $comanda->id,
            'mesa_id' => $comanda->mesa_id,
            'empresa_id' => $comanda->empresa_id,
            'unidade_id' => $comanda->unidade_id,
            'usuario_fechamento_id' => $usuarioId,
            'fechada_em' => $comanda->fechada_em?->toIso8601String(),
            'totais' => [
                'subtotal' => (string) $comanda->subtotal,
                'taxa_servico' => (string) $comanda->taxa_servico,
                'desconto' => (string) $comanda->desconto,
                'total' => (string) $comanda->total,
            ],
            'itens' => $comanda->itens->map(fn ($i) => [
                'produto_id' => $i->produto_id,
                'nome' => $i->nome_produto,
                'quantidade' => $i->quantidade,
                'valor_unitario' => (string) $i->valor_unitario,
                'valor_total' => (string) $i->valor_total,
                'status' => $i->status->value,
            ])->values()->all(),
            'pagamentos' => $comanda->pagamentos->map(fn ($p) => [
                'forma' => $p->forma_pagamento,
                'valor_pago' => (string) $p->valor_pago,
                'troco' => (string) $p->troco,
            ])->values()->all(),
            'estoque' => ['status' => 'pendente_baixa', 'observacao' => 'Reservado para futura baixa automática por produto_id/quantidade.'],
            'financeiro' => ['status' => 'pendente_lancamento', 'observacao' => 'Reservado para contas a receber / caixa.'],
            'fiscal' => ['status' => 'pendente_nfce', 'observacao' => 'Reservado para módulo fiscal (NFC-e).'],
        ];
    }
}
