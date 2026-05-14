<?php

namespace App\Models;

use App\Enums\Mesas\ComandaItemStatus;
use App\Enums\Mesas\ComandaSetorDestino;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComandaItem extends Model
{
    protected $table = 'comanda_itens';

    protected $fillable = [
        'comanda_id',
        'produto_id',
        'nome_produto',
        'quantidade',
        'valor_unitario',
        'valor_total',
        'observacao',
        'setor_destino',
        'status',
        'enviado_cozinha_em',
        'pronto_em',
        'entregue_em',
    ];

    protected function casts(): array
    {
        return [
            'status' => ComandaItemStatus::class,
            'setor_destino' => ComandaSetorDestino::class,
            'valor_unitario' => 'decimal:2',
            'valor_total' => 'decimal:2',
            'quantidade' => 'integer',
            'enviado_cozinha_em' => 'datetime',
            'pronto_em' => 'datetime',
            'entregue_em' => 'datetime',
        ];
    }

    public function comanda(): BelongsTo
    {
        return $this->belongsTo(Comanda::class, 'comanda_id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}
