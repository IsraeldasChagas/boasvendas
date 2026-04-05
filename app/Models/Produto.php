<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Produto extends Model
{
    protected $table = 'produtos';

    protected $fillable = [
        'empresa_id',
        'categoria_id',
        'sku',
        'nome',
        'preco',
        'estoque',
        'descricao',
        'visivel_loja',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'preco' => 'decimal:2',
            'estoque' => 'integer',
            'visivel_loja' => 'boolean',
            'ativo' => 'boolean',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}
