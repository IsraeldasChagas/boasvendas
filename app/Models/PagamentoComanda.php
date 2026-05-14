<?php

namespace App\Models;

use App\Enums\Mesas\PagamentoComandaStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagamentoComanda extends Model
{
    protected $table = 'pagamentos_comanda';

    protected $fillable = [
        'comanda_id',
        'forma_pagamento',
        'valor_pago',
        'troco',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => PagamentoComandaStatus::class,
            'valor_pago' => 'decimal:2',
            'troco' => 'decimal:2',
        ];
    }

    public function comanda(): BelongsTo
    {
        return $this->belongsTo(Comanda::class, 'comanda_id');
    }
}
