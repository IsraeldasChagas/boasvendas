<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VeAcerto extends Model
{
    protected $table = 've_acertos';

    public const STATUS_ABERTO = 'aberto';

    public const STATUS_CONCLUIDO = 'concluido';

    protected $fillable = [
        'empresa_id',
        've_ponto_id',
        've_remessa_id',
        'data_acerto',
        'valor_vendas',
        'valor_repasse_unitario',
        'valor_repasse',
        'status',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'data_acerto' => 'date',
            'valor_vendas' => 'decimal:2',
            'valor_repasse_unitario' => 'decimal:2',
            'valor_repasse' => 'decimal:2',
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

    public function remessa(): BelongsTo
    {
        return $this->belongsTo(VeRemessa::class, 've_remessa_id');
    }

    public static function rotulosStatus(): array
    {
        return [
            self::STATUS_ABERTO => 'Aberto',
            self::STATUS_CONCLUIDO => 'Concluído',
        ];
    }

    public function rotuloStatus(): string
    {
        return self::rotulosStatus()[$this->status] ?? $this->status;
    }

    public function classeBadgeStatus(): string
    {
        return $this->status === self::STATUS_CONCLUIDO
            ? 'bg-success-subtle text-success'
            : 'bg-warning-subtle text-warning';
    }
}
