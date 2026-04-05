<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assinatura extends Model
{
    protected $table = 'assinaturas';

    protected $fillable = [
        'empresa_id',
        'empresa_nome',
        'plano_id',
        'valor_mensal',
        'proxima_cobranca',
        'gateway',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'valor_mensal' => 'decimal:2',
            'proxima_cobranca' => 'date',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function plano(): BelongsTo
    {
        return $this->belongsTo(Plano::class, 'plano_id');
    }

    public static function statusRotulos(): array
    {
        return [
            'paga' => 'Paga',
            'pendente' => 'Pendente',
        ];
    }
}
