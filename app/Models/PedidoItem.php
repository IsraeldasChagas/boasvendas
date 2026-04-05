<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoItem extends Model
{
    protected $table = 'pedido_itens';

    protected $fillable = [
        'pedido_id',
        'produto_id',
        'nome_produto',
        'preco_unitario',
        'quantidade',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'preco_unitario' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}
