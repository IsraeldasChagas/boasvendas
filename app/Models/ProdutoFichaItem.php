<?php

namespace App\Models;

use App\Enums\Estoque\UnidadeMedida;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Linha da ficha técnica: quanto de um insumo a receita consome.
 * `quantidade`/`unidade` é o que o usuário digitou; `quantidade_base`
 * é o equivalente na unidade base do insumo (usado na baixa).
 */
class ProdutoFichaItem extends Model
{
    protected $table = 'produto_ficha_itens';

    protected $fillable = [
        'empresa_id',
        'produto_id',
        'insumo_id',
        'quantidade',
        'unidade',
        'quantidade_base',
        'observacao',
        'ordem',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'float',
            'quantidade_base' => 'float',
            'unidade' => UnidadeMedida::class,
            'ordem' => 'integer',
        ];
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class, 'insumo_id');
    }

    /** Quantidade como o usuário cadastrou (ex.: "0,2 kg"). */
    public function quantidadeFormatada(): string
    {
        $unidade = $this->unidade ?? UnidadeMedida::Grama;
        $valor = rtrim(rtrim(number_format((float) $this->quantidade, 3, ',', '.'), '0'), ',');

        return $valor.' '.$unidade->sigla();
    }

    /** Quantas porções o saldo atual do insumo ainda permite produzir. */
    public function porcoesPossiveis(): ?int
    {
        $base = (float) $this->quantidade_base;
        if ($base <= 0) {
            return null;
        }

        $saldo = (float) ($this->insumo?->saldo ?? 0);

        return (int) floor($saldo / $base);
    }
}
