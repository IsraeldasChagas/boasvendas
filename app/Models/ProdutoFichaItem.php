<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item da ficha técnica: insumo consumido por unidade vendida do produto final.
 */
class ProdutoFichaItem extends Model
{
    protected $table = 'produto_ficha_itens';

    protected $fillable = [
        'empresa_id',
        'produto_id',
        'insumo_produto_id',
        'quantidade',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'integer',
        ];
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'insumo_produto_id');
    }
}
