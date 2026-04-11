<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaixaTurno extends Model
{
    protected $table = 'caixa_turnos';

    public const STATUS_ABERTO = 'aberto';

    public const STATUS_FECHADO = 'fechado';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'aberto_em',
        'fechado_em',
        'valor_abertura',
        'valor_conferido_fechamento',
        'status',
        'obs_abertura',
        'obs_fechamento',
    ];

    protected function casts(): array
    {
        return [
            'aberto_em' => 'datetime',
            'fechado_em' => 'datetime',
            'valor_abertura' => 'decimal:2',
            'valor_conferido_fechamento' => 'decimal:2',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function movimentos(): HasMany
    {
        return $this->hasMany(CaixaMovimento::class, 'caixa_turno_id')->orderBy('created_at');
    }

    public function totalEntradasMovimentos(): float
    {
        return (float) $this->movimentos()
            ->whereIn('tipo', [
                CaixaMovimento::TIPO_SUPRIMENTO,
                CaixaMovimento::TIPO_VENDA_AVULSA,
                CaixaMovimento::TIPO_ENTRADA_MANUAL,
            ])
            ->sum('valor');
    }

    public function totalSaidasMovimentos(): float
    {
        return (float) $this->movimentos()
            ->whereIn('tipo', [CaixaMovimento::TIPO_SANGRIA, CaixaMovimento::TIPO_SAIDA_MANUAL])
            ->sum('valor');
    }

    public function saldoEsperado(): float
    {
        return (float) $this->valor_abertura + $this->totalEntradasMovimentos() - $this->totalSaidasMovimentos();
    }

    public function diferencaFechamento(): ?float
    {
        if ($this->valor_conferido_fechamento === null) {
            return null;
        }

        return (float) $this->valor_conferido_fechamento - $this->saldoEsperado();
    }
}
