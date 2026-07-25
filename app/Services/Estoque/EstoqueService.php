<?php

namespace App\Services\Estoque;

use App\Enums\Estoque\EstoqueMovimentoTipo;
use App\Models\EstoqueMovimento;
use App\Models\Produto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Único ponto de mutação de produtos.estoque: baixa/devolução por venda,
 * reposição e ajuste manual, sempre com lock de linha e movimento gravado.
 * Ficha técnica: venda do produto final também baixa os insumos vinculados.
 */
class EstoqueService
{
    /**
     * Baixa estoque (saída de venda ou remessa).
     *
     * @param  bool  $comFicha  também baixa insumos da ficha técnica (não bloqueia a venda)
     * @param  bool  $bloquearSeInsuficiente  false = baixa parcial até zerar (ex.: fechamento de mesa)
     *
     * @throws ValidationException
     */
    public function baixar(
        Produto|int $produto,
        int $quantidade,
        EstoqueMovimentoTipo $tipo,
        ?Model $referencia = null,
        ?string $observacao = null,
        ?int $userId = null,
        bool $comFicha = false,
        bool $bloquearSeInsuficiente = true,
    ): ?EstoqueMovimento {
        $quantidade = abs($quantidade);
        if ($quantidade === 0) {
            return null;
        }

        return DB::transaction(function () use ($produto, $quantidade, $tipo, $referencia, $observacao, $userId, $comFicha, $bloquearSeInsuficiente) {
            $p = $this->produtoBloqueado($produto);

            $mov = null;
            if ($this->controla($p)) {
                $delta = -$quantidade;
                if ((int) $p->estoque < $quantidade) {
                    if ($bloquearSeInsuficiente) {
                        throw ValidationException::withMessages([
                            'estoque' => 'O produto "'.$p->nome.'" não tem estoque suficiente. Disponível: '.(int) $p->estoque.'.',
                        ]);
                    }
                    $delta = -(int) $p->estoque;
                    $observacao = trim(($observacao ?? '').' (saldo insuficiente; baixa parcial de '.abs($delta).' de '.$quantidade.')');
                }
                if ($delta !== 0) {
                    $mov = $this->aplicarDelta($p, $delta, $tipo, $referencia, $observacao, $userId);
                }
            }

            if ($comFicha) {
                $this->consumirFicha($p, $quantidade, $referencia, $userId);
            }

            return $mov;
        });
    }

    /**
     * Devolve estoque (cancelamento/acerto).
     *
     * @param  bool  $comFicha  também devolve insumos da ficha técnica
     */
    public function devolver(
        Produto|int $produto,
        int $quantidade,
        EstoqueMovimentoTipo $tipo,
        ?Model $referencia = null,
        ?string $observacao = null,
        ?int $userId = null,
        bool $comFicha = false,
    ): ?EstoqueMovimento {
        $quantidade = abs($quantidade);
        if ($quantidade === 0) {
            return null;
        }

        return DB::transaction(function () use ($produto, $quantidade, $tipo, $referencia, $observacao, $userId, $comFicha) {
            $p = $this->produtoBloqueado($produto);

            $mov = null;
            if ($this->controla($p)) {
                $mov = $this->aplicarDelta($p, $quantidade, $tipo, $referencia, $observacao, $userId);
            }

            if ($comFicha) {
                $this->devolverFicha($p, $quantidade, $referencia, $userId);
            }

            return $mov;
        });
    }

    /** Reposição manual: soma quantidade ao saldo. */
    public function repor(
        Produto|int $produto,
        int $quantidade,
        ?string $observacao = null,
        ?int $userId = null,
    ): EstoqueMovimento {
        if ($quantidade < 1) {
            throw ValidationException::withMessages([
                'quantidade' => 'Informe uma quantidade maior que zero para repor.',
            ]);
        }

        return DB::transaction(function () use ($produto, $quantidade, $observacao, $userId) {
            $p = $this->produtoBloqueado($produto);
            $this->garantirControle($p);

            return $this->aplicarDelta($p, $quantidade, EstoqueMovimentoTipo::Reposicao, null, $observacao, $userId);
        });
    }

    /** Ajuste de inventário: define o saldo absoluto. */
    public function ajustar(
        Produto|int $produto,
        int $novoSaldo,
        ?string $observacao = null,
        ?int $userId = null,
    ): ?EstoqueMovimento {
        if ($novoSaldo < 0) {
            throw ValidationException::withMessages([
                'estoque' => 'O saldo não pode ser negativo.',
            ]);
        }

        return DB::transaction(function () use ($produto, $novoSaldo, $observacao, $userId) {
            $p = $this->produtoBloqueado($produto);
            $this->garantirControle($p);

            $delta = $novoSaldo - (int) $p->estoque;
            if ($delta === 0) {
                return null;
            }

            return $this->aplicarDelta($p, $delta, EstoqueMovimentoTipo::Ajuste, null, $observacao, $userId);
        });
    }

    /**
     * Valida saldo suficiente antes de vender (quando o produto controla estoque).
     *
     * @throws ValidationException
     */
    public function garantirDisponivel(Produto $produto, int $quantidade, string $mensagemCampo = 'itens'): void
    {
        if (! $this->controla($produto) || $quantidade < 1) {
            return;
        }

        if ((int) $produto->estoque < $quantidade) {
            throw ValidationException::withMessages([
                $mensagemCampo => 'O produto "'.$produto->nome.'" não tem estoque suficiente. Disponível: '.(int) $produto->estoque.'.',
            ]);
        }
    }

    public function controla(Produto $produto): bool
    {
        return $produto->controlaEstoque();
    }

    /** Consome insumos da ficha (baixa parcial; nunca bloqueia a venda do produto final). */
    private function consumirFicha(Produto $produto, int $quantidadeVendida, ?Model $referencia, ?int $userId): void
    {
        foreach ($this->itensFicha($produto) as $item) {
            $insumo = $this->produtoBloqueado((int) $item->insumo_produto_id);
            if (! $this->controla($insumo)) {
                continue;
            }

            $necessario = (int) $item->quantidade * $quantidadeVendida;
            $delta = -min($necessario, (int) $insumo->estoque);
            $obs = 'Ficha técnica de "'.$produto->nome.'"';
            if (abs($delta) < $necessario) {
                $obs .= ' (saldo insuficiente; baixa parcial de '.abs($delta).' de '.$necessario.')';
            }
            if ($delta !== 0) {
                $this->aplicarDelta($insumo, $delta, EstoqueMovimentoTipo::ConsumoFicha, $referencia, $obs, $userId);
            }
        }
    }

    /** Devolve insumos da ficha (cancelamento do produto final). */
    private function devolverFicha(Produto $produto, int $quantidadeDevolvida, ?Model $referencia, ?int $userId): void
    {
        foreach ($this->itensFicha($produto) as $item) {
            $insumo = $this->produtoBloqueado((int) $item->insumo_produto_id);
            if (! $this->controla($insumo)) {
                continue;
            }

            $qtd = (int) $item->quantidade * $quantidadeDevolvida;
            if ($qtd > 0) {
                $this->aplicarDelta($insumo, $qtd, EstoqueMovimentoTipo::ConsumoFicha, $referencia, 'Devolução — ficha técnica de "'.$produto->nome.'"', $userId);
            }
        }
    }

    /** @return \Illuminate\Support\Collection<int, \App\Models\ProdutoFichaItem> */
    private function itensFicha(Produto $produto)
    {
        if (! Schema::hasTable('produto_ficha_itens')) {
            return collect();
        }

        return \App\Models\ProdutoFichaItem::query()
            ->where('produto_id', $produto->id)
            ->get();
    }

    private function aplicarDelta(
        Produto $produto,
        int $delta,
        EstoqueMovimentoTipo $tipo,
        ?Model $referencia,
        ?string $observacao,
        ?int $userId,
    ): EstoqueMovimento {
        $novo = (int) $produto->estoque + $delta;
        if ($novo < 0) {
            throw ValidationException::withMessages([
                'estoque' => 'O produto "'.$produto->nome.'" ficaria com estoque negativo.',
            ]);
        }

        $produto->estoque = $novo;
        $produto->save();

        return EstoqueMovimento::query()->create([
            'empresa_id' => $produto->empresa_id,
            'produto_id' => $produto->id,
            'tipo' => $tipo,
            'delta' => $delta,
            'saldo_apos' => $novo,
            'referencia_type' => $referencia?->getMorphClass(),
            'referencia_id' => $referencia?->getKey(),
            'observacao' => filled($observacao) ? mb_substr(trim((string) $observacao), 0, 500) : null,
            'user_id' => $userId,
        ]);
    }

    private function garantirControle(Produto $produto): void
    {
        if (! $this->controla($produto)) {
            throw ValidationException::withMessages([
                'produto' => 'Este produto não controla estoque.',
            ]);
        }
    }

    private function produtoBloqueado(Produto|int $produto): Produto
    {
        $id = $produto instanceof Produto ? $produto->id : $produto;

        $p = Produto::query()->whereKey($id)->lockForUpdate()->first();
        if ($p === null) {
            throw ValidationException::withMessages([
                'produto' => 'Produto não encontrado.',
            ]);
        }

        return $p;
    }
}
