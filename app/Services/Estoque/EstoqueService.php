<?php

namespace App\Services\Estoque;

use App\Enums\Estoque\EstoqueMovimentoTipo;
use App\Enums\Estoque\UnidadeMedida;
use App\Models\EstoqueMovimento;
use App\Models\Insumo;
use App\Models\Produto;
use App\Models\ProdutoFichaItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Único ponto de mutação de estoque: produtos acabados (`produtos.estoque`,
 * inteiro) e insumos (`insumos.saldo`, fracionado em g/ml/un). Sempre com lock
 * de linha e movimento gravado em `estoque_movimentos`.
 *
 * Vender um prato com ficha técnica consome os insumos da receita.
 */
class EstoqueService
{
    /**
     * Baixa produto acabado (venda/remessa) e, se pedido, consome a receita.
     *
     * @param  bool  $comFicha  consome os insumos da ficha técnica
     * @param  bool  $bloquearSeInsuficiente  false = baixa parcial (ex.: fechamento de mesa)
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
                    $mov = $this->aplicarDeltaProduto($p, $delta, $tipo, $referencia, $observacao, $userId);
                }
            }

            if ($comFicha) {
                $this->consumirReceita($p, $quantidade, $referencia, $userId);
            }

            return $mov;
        });
    }

    /** Devolve produto acabado (cancelamento/acerto) e, se pedido, os insumos. */
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
                $mov = $this->aplicarDeltaProduto($p, $quantidade, $tipo, $referencia, $observacao, $userId);
            }

            if ($comFicha) {
                $this->devolverReceita($p, $quantidade, $referencia, $userId);
            }

            return $mov;
        });
    }

    /** Reposição manual de produto acabado. */
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

            return $this->aplicarDeltaProduto($p, $quantidade, EstoqueMovimentoTipo::Reposicao, null, $observacao, $userId);
        });
    }

    /** Ajuste de inventário de produto acabado: define o saldo absoluto. */
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

            return $this->aplicarDeltaProduto($p, $delta, EstoqueMovimentoTipo::Ajuste, null, $observacao, $userId);
        });
    }

    /**
     * Reposição de insumo (compra). A quantidade é convertida da unidade
     * informada para a base do insumo.
     */
    public function reporInsumo(
        Insumo|int $insumo,
        float $quantidade,
        UnidadeMedida $unidade,
        ?string $observacao = null,
        ?int $userId = null,
    ): EstoqueMovimento {
        if ($quantidade <= 0) {
            throw ValidationException::withMessages([
                'quantidade' => 'Informe uma quantidade maior que zero.',
            ]);
        }

        return DB::transaction(function () use ($insumo, $quantidade, $unidade, $observacao, $userId) {
            $i = $this->insumoBloqueado($insumo);
            $base = $this->converterParaBase($quantidade, $unidade, $i);

            return $this->aplicarDeltaInsumo($i, $base, EstoqueMovimentoTipo::Reposicao, null, $observacao, $userId);
        });
    }

    /** Ajuste de inventário de insumo: define o saldo absoluto na unidade informada. */
    public function ajustarInsumo(
        Insumo|int $insumo,
        float $novoSaldo,
        UnidadeMedida $unidade,
        ?string $observacao = null,
        ?int $userId = null,
    ): ?EstoqueMovimento {
        if ($novoSaldo < 0) {
            throw ValidationException::withMessages([
                'novo_saldo' => 'O saldo não pode ser negativo.',
            ]);
        }

        return DB::transaction(function () use ($insumo, $novoSaldo, $unidade, $observacao, $userId) {
            $i = $this->insumoBloqueado($insumo);
            $baseAlvo = $this->converterParaBase($novoSaldo, $unidade, $i);

            $delta = round($baseAlvo - (float) $i->saldo, 3);
            if (abs($delta) < 0.001) {
                return null;
            }

            return $this->aplicarDeltaInsumo($i, $delta, EstoqueMovimentoTipo::Ajuste, null, $observacao, $userId);
        });
    }

    /**
     * Valida saldo do produto acabado antes de vender.
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

    /** Converte a quantidade informada para a unidade base do insumo. */
    public function converterParaBase(float $quantidade, UnidadeMedida $unidade, Insumo $insumo): float
    {
        $base = $insumo->unidadeBase();

        if (! $unidade->compativelCom($base)) {
            throw ValidationException::withMessages([
                'unidade' => 'A unidade '.$unidade->sigla().' não é compatível com "'.$insumo->nome.'" (medido em '.$base->sigla().').',
            ]);
        }

        return $unidade->paraBase($quantidade);
    }

    /**
     * Consome a receita. Ficha que rende N porções consome 1 receita a cada N
     * unidades vendidas. Falta de insumo não trava a venda: baixa parcial
     * registrada no histórico.
     */
    private function consumirReceita(Produto $produto, int $quantidadeVendida, ?Model $referencia, ?int $userId): void
    {
        foreach ($this->itensDaFicha($produto) as $item) {
            $insumo = $this->insumoBloqueado((int) $item->insumo_id);
            $necessario = $this->totalNecessario($item, $produto, $quantidadeVendida);
            if ($necessario <= 0) {
                continue;
            }

            $disponivel = max(0, (float) $insumo->saldo);
            $consumo = min($necessario, $disponivel);
            if ($consumo <= 0) {
                continue;
            }

            $obs = 'Receita de "'.$produto->nome.'"';
            if ($consumo < $necessario) {
                $obs .= ' (insumo insuficiente; consumo parcial de '
                    .UnidadeMedida::formatar($consumo, $insumo->unidadeBase()).' de '
                    .UnidadeMedida::formatar($necessario, $insumo->unidadeBase()).')';
            }

            $this->aplicarDeltaInsumo($insumo, -$consumo, EstoqueMovimentoTipo::ConsumoFicha, $referencia, $obs, $userId);
        }
    }

    /** Devolve à receita os insumos de um produto cancelado. */
    private function devolverReceita(Produto $produto, int $quantidadeDevolvida, ?Model $referencia, ?int $userId): void
    {
        foreach ($this->itensDaFicha($produto) as $item) {
            $insumo = $this->insumoBloqueado((int) $item->insumo_id);
            $total = $this->totalNecessario($item, $produto, $quantidadeDevolvida);
            if ($total <= 0) {
                continue;
            }

            $this->aplicarDeltaInsumo(
                $insumo,
                $total,
                EstoqueMovimentoTipo::ConsumoFicha,
                $referencia,
                'Devolução — receita de "'.$produto->nome.'"',
                $userId,
            );
        }
    }

    /** Quantidade base de um insumo para produzir N unidades do produto. */
    private function totalNecessario(ProdutoFichaItem $item, Produto $produto, int $quantidade): float
    {
        $porPorcao = (float) $item->quantidade_base / max(1, $produto->fichaRendimento());

        return round($porPorcao * $quantidade, 3);
    }

    /** @return Collection<int, ProdutoFichaItem> */
    private function itensDaFicha(Produto $produto)
    {
        if (! Schema::hasTable('produto_ficha_itens') || ! Schema::hasTable('insumos')) {
            return collect();
        }

        return ProdutoFichaItem::query()->where('produto_id', $produto->id)->get();
    }

    private function aplicarDeltaProduto(
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

        return $this->registrar([
            'empresa_id' => $produto->empresa_id,
            'produto_id' => $produto->id,
            'tipo' => $tipo,
            'delta' => $delta,
            'saldo_apos' => $novo,
        ], $referencia, $observacao, $userId);
    }

    private function aplicarDeltaInsumo(
        Insumo $insumo,
        float $delta,
        EstoqueMovimentoTipo $tipo,
        ?Model $referencia,
        ?string $observacao,
        ?int $userId,
    ): EstoqueMovimento {
        $novo = round((float) $insumo->saldo + $delta, 3);
        if ($novo < 0) {
            throw ValidationException::withMessages([
                'saldo' => 'O insumo "'.$insumo->nome.'" ficaria com saldo negativo.',
            ]);
        }

        $insumo->saldo = $novo;
        $insumo->save();

        return $this->registrar([
            'empresa_id' => $insumo->empresa_id,
            'insumo_id' => $insumo->id,
            'tipo' => $tipo,
            'delta' => $delta,
            'saldo_apos' => $novo,
            'unidade' => $insumo->unidadeBase(),
        ], $referencia, $observacao, $userId);
    }

    /** @param  array<string, mixed>  $dados */
    private function registrar(array $dados, ?Model $referencia, ?string $observacao, ?int $userId): EstoqueMovimento
    {
        return EstoqueMovimento::query()->create($dados + [
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

    private function insumoBloqueado(Insumo|int $insumo): Insumo
    {
        $id = $insumo instanceof Insumo ? $insumo->id : $insumo;

        $i = Insumo::query()->whereKey($id)->lockForUpdate()->first();
        if ($i === null) {
            throw ValidationException::withMessages([
                'insumo' => 'Insumo não encontrado.',
            ]);
        }

        return $i;
    }
}
