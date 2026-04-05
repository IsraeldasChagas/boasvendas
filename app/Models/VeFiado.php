<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VeFiado extends Model
{
    protected $table = 've_fiados';

    public const STATUS_ABERTO = 'aberto';

    public const STATUS_QUITADO = 'quitado';

    protected $fillable = [
        'empresa_id',
        've_ponto_id',
        'contraparte',
        'descricao',
        'valor',
        'status',
        'vencimento',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'vencimento' => 'date',
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

    public static function rotulosStatus(): array
    {
        return [
            self::STATUS_ABERTO => 'Em aberto',
            self::STATUS_QUITADO => 'Quitado',
        ];
    }

    public function rotuloStatus(): string
    {
        return self::rotulosStatus()[$this->status] ?? $this->status;
    }

    public function classeBadgeStatus(): string
    {
        return $this->status === self::STATUS_QUITADO
            ? 'bg-success-subtle text-success'
            : 'bg-warning-subtle text-warning';
    }

    /** @return 'quitado'|'atrasado'|'em_dia' */
    public function situacaoVisual(): string
    {
        if ($this->status === self::STATUS_QUITADO) {
            return 'quitado';
        }
        if ($this->vencimento && $this->vencimento->lt(Carbon::today())) {
            return 'atrasado';
        }

        return 'em_dia';
    }

    public function rotuloSituacao(): string
    {
        return match ($this->situacaoVisual()) {
            'quitado' => 'Quitado',
            'atrasado' => 'Atrasado',
            'em_dia' => 'Em dia',
            default => 'Em dia',
        };
    }

    public function classeBadgeSituacao(): string
    {
        return match ($this->situacaoVisual()) {
            'quitado' => 'bg-secondary-subtle text-secondary',
            'atrasado' => 'bg-danger-subtle text-danger',
            default => 'bg-success-subtle text-success',
        };
    }

    public function podeDarBaixa(): bool
    {
        return $this->status === self::STATUS_ABERTO;
    }
}
