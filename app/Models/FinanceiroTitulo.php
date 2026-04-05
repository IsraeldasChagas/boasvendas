<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceiroTitulo extends Model
{
    protected $table = 'financeiro_titulos';

    public const TIPO_RECEBER = 'receber';

    public const TIPO_PAGAR = 'pagar';

    public const STATUS_ABERTO = 'aberto';

    public const STATUS_PAGO = 'pago';

    protected $fillable = [
        'empresa_id',
        'tipo',
        'contraparte',
        'descricao',
        'valor',
        'vencimento',
        'status',
        'pago_em',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'vencimento' => 'date',
            'pago_em' => 'date',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function situacaoExibicao(): string
    {
        if ($this->status === self::STATUS_PAGO) {
            return 'pago';
        }

        $hoje = Carbon::today();

        return $this->vencimento->lt($hoje) ? 'atrasado' : 'aberto';
    }

    public function rotuloSituacao(): string
    {
        return match ($this->situacaoExibicao()) {
            'pago' => 'Pago',
            'atrasado' => 'Atrasado',
            default => 'Aberto',
        };
    }

    public function classeBadgeSituacao(): string
    {
        return match ($this->situacaoExibicao()) {
            'pago' => 'bg-success-subtle text-success',
            'atrasado' => 'bg-danger-subtle text-danger',
            default => 'bg-warning-subtle text-warning',
        };
    }
}
