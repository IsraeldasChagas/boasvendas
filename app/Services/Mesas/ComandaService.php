<?php

namespace App\Services\Mesas;

use App\Enums\Mesas\ComandaItemStatus;
use App\Enums\Mesas\ComandaStatus;
use App\Enums\Mesas\ComandaSetorDestino;
use App\Models\Comanda;
use App\Models\ComandaItem;
use App\Models\Mesa;
use App\Models\MesaConfiguracao;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ComandaService
{
    public function __construct(
        private readonly MesaService $mesaService,
    ) {}

    public function abrirNaMesa(
        Mesa $mesa,
        ?string $clienteNome,
        ?string $clienteDocumento,
        ?int $garcomId,
        ?string $observacao,
    ): Comanda {
        $this->mesaService->garantirPodeAbrir($mesa);

        if (($garcomId === null || $garcomId === 0) && Auth::check()) {
            $auth = Auth::user();
            if ($auth instanceof User && $auth->temAcessoRestritoAoPainelEmpresa()) {
                $garcomId = (int) $auth->id;
            }
        }

        $config = MesaConfiguracao::obterOuCriarPadrao((int) $mesa->empresa_id, $mesa->unidade_id !== null ? (int) $mesa->unidade_id : null);
        if ($config->exigir_garcom_abertura && ($garcomId === null || $garcomId === 0)) {
            throw new \InvalidArgumentException('Selecione o garçom responsável.');
        }
        if ($garcomId !== null) {
            $ok = User::query()
                ->where('id', $garcomId)
                ->where('empresa_id', $mesa->empresa_id)
                ->exists();
            if (! $ok) {
                throw new \InvalidArgumentException('Garçom inválido para esta empresa.');
            }
        }

        return DB::transaction(function () use ($mesa, $clienteNome, $clienteDocumento, $garcomId, $observacao) {
            $comanda = new Comanda;
            $comanda->empresa_id = $mesa->empresa_id;
            $comanda->unidade_id = $mesa->unidade_id;
            $comanda->mesa_id = $mesa->id;
            $comanda->cliente_nome = $clienteNome;
            $comanda->cliente_documento = $clienteDocumento;
            $comanda->garcom_id = $garcomId;
            $comanda->status = ComandaStatus::Aberta;
            $comanda->subtotal = 0;
            $comanda->taxa_servico = 0;
            $comanda->desconto = 0;
            $comanda->total = 0;
            $comanda->observacao = $observacao;
            $comanda->save();

            $this->mesaService->sincronizarStatusMesa($mesa);

            return $comanda;
        });
    }

    public function adicionarItem(
        Comanda $comanda,
        int $produtoId,
        int $quantidade,
        ?string $observacaoItem,
        ComandaSetorDestino $setor,
    ): ComandaItem {
        if (! $comanda->estaAbertaParaConsumo()) {
            throw new \RuntimeException('Comanda não aceita novos itens.');
        }
        if ($comanda->status === ComandaStatus::ContaSolicitada) {
            throw new \RuntimeException('Conta já foi solicitada.');
        }

        $produto = Produto::query()
            ->where('id', $produtoId)
            ->where('empresa_id', $comanda->empresa_id)
            ->where('ativo', true)
            ->firstOrFail();

        $q = max(1, $quantidade);
        $unit = (float) $produto->preco;
        $total = round($unit * $q, 2);

        return DB::transaction(function () use ($comanda, $produto, $q, $unit, $total, $observacaoItem, $setor) {
            $item = new ComandaItem;
            $item->comanda_id = $comanda->id;
            $item->produto_id = $produto->id;
            $item->nome_produto = $produto->nome;
            $item->quantidade = $q;
            $item->valor_unitario = $unit;
            $item->valor_total = $total;
            $item->observacao = $observacaoItem;
            $item->setor_destino = $setor;
            $item->status = ComandaItemStatus::Pendente;
            $item->save();

            if ($comanda->status === ComandaStatus::Aberta) {
                $comanda->status = ComandaStatus::EmConsumo;
                $comanda->save();
            }

            $this->recalcularTotaisProvisorios($comanda);
            $this->mesaService->sincronizarStatusMesa($comanda->mesa);

            return $item->fresh();
        });
    }

    public function removerItem(ComandaItem $item): void
    {
        $comanda = $item->comanda;
        if (! $comanda->estaAbertaParaConsumo()) {
            throw new \RuntimeException('Comanda encerrada.');
        }
        if ($item->status !== ComandaItemStatus::Pendente) {
            throw new \RuntimeException('Somente itens pendentes podem ser removidos.');
        }

        DB::transaction(function () use ($item, $comanda) {
            $item->delete();
            $this->recalcularTotaisProvisorios($comanda);
            $this->mesaService->sincronizarStatusMesa($comanda->mesa);
        });
    }

    public function enviarPendentesParaCozinha(Comanda $comanda): int
    {
        if (! $comanda->estaAbertaParaConsumo()) {
            throw new \RuntimeException('Comanda não permite envio.');
        }
        if ($comanda->status === ComandaStatus::ContaSolicitada) {
            throw new \RuntimeException('Conta solicitada: não é possível enviar novos itens à cozinha.');
        }

        return DB::transaction(function () use ($comanda) {
            $n = 0;
            $comanda->itens()
                ->where('status', ComandaItemStatus::Pendente)
                ->orderBy('id')
                ->each(function (ComandaItem $item) use (&$n) {
                    $item->status = ComandaItemStatus::Enviado;
                    $item->enviado_cozinha_em = now();
                    $item->save();
                    $n++;
                });

            $this->mesaService->sincronizarStatusMesa($comanda->mesa);

            return $n;
        });
    }

    public function subtotalItens(Comanda $comanda): string
    {
        $s = (float) $comanda->itens()
            ->where('status', '!=', ComandaItemStatus::Cancelado)
            ->sum('valor_total');

        return number_format($s, 2, '.', '');
    }

    public function recalcularTotaisProvisorios(Comanda $comanda): void
    {
        $sub = (float) $this->subtotalItens($comanda);
        $taxa = (float) $comanda->taxa_servico;
        $desc = (float) $comanda->desconto;
        $total = max(0, round($sub + $taxa - $desc, 2));

        $comanda->subtotal = number_format($sub, 2, '.', '');
        $comanda->total = number_format($total, 2, '.', '');
        $comanda->save();
    }

    public function aplicarTaxaDesconto(Comanda $comanda, ?float $taxaPercentual, float $desconto): void
    {
        if (! $comanda->estaAbertaParaConsumo()) {
            throw new \RuntimeException('Comanda não permite alteração de totais.');
        }

        $sub = (float) $this->subtotalItens($comanda);
        $taxa = 0.0;
        if ($taxaPercentual !== null && $taxaPercentual > 0) {
            $taxa = round($sub * ($taxaPercentual / 100.0), 2);
        }

        $comanda->taxa_servico_percentual = $taxaPercentual !== null ? number_format($taxaPercentual, 2, '.', '') : null;
        $comanda->taxa_servico = number_format($taxa, 2, '.', '');
        $comanda->desconto = number_format(max(0, $desconto), 2, '.', '');
        $comanda->total = number_format(max(0, round($sub + $taxa - (float) $comanda->desconto, 2)), 2, '.', '');
        $comanda->subtotal = number_format($sub, 2, '.', '');
        $comanda->save();
    }

    public function atualizarCabecalho(Comanda $comanda, ?string $nome, ?string $doc, ?int $garcomId, ?string $obs): void
    {
        if (! $comanda->estaAbertaParaConsumo()) {
            throw new \RuntimeException('Comanda encerrada.');
        }
        if ($garcomId !== null) {
            $ok = User::query()->where('id', $garcomId)->where('empresa_id', $comanda->empresa_id)->exists();
            if (! $ok) {
                throw new \InvalidArgumentException('Garçom inválido.');
            }
        }
        $comanda->cliente_nome = $nome;
        $comanda->cliente_documento = $doc;
        $comanda->garcom_id = $garcomId;
        $comanda->observacao = $obs;
        $comanda->save();
    }
}
