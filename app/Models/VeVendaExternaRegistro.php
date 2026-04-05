<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VeVendaExternaRegistro extends Model
{
    protected $table = 've_venda_externa_registros';

    protected $fillable = [
        'empresa_id',
        've_ponto_id',
        'data_venda',
        'valor',
        'referencia',
    ];

    protected function casts(): array
    {
        return [
            'data_venda' => 'date',
            'valor' => 'decimal:2',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function ponto(): BelongsTo
    {
        return $this->belongsTo(VePonto::class, 've_ponto_id');
    }
}
