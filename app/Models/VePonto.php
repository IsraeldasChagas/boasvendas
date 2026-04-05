<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VePonto extends Model
{
    protected $table = 've_pontos';

    public const STATUS_ATIVO = 'ativo';

    public const STATUS_PAUSADO = 'pausado';

    protected $fillable = [
        'empresa_id',
        'nome',
        'regiao',
        'status',
        'proximo_acerto_em',
        'ultimo_acerto_em',
    ];

    protected function casts(): array
    {
        return [
            'proximo_acerto_em' => 'datetime',
            'ultimo_acerto_em' => 'datetime',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function remessas(): HasMany
    {
        return $this->hasMany(VeRemessa::class, 've_ponto_id');
    }

    public function fiados(): HasMany
    {
        return $this->hasMany(VeFiado::class, 've_ponto_id');
    }

    public function registrosVenda(): HasMany
    {
        return $this->hasMany(VeVendaExternaRegistro::class, 've_ponto_id');
    }

    public static function rotulosStatus(): array
    {
        return [
            self::STATUS_ATIVO => 'Ativo',
            self::STATUS_PAUSADO => 'Pausado',
        ];
    }

    public function rotuloStatus(): string
    {
        return self::rotulosStatus()[$this->status] ?? $this->status;
    }

    public function classeBadgeStatus(): string
    {
        return $this->status === self::STATUS_ATIVO
            ? 'bg-success-subtle text-success'
            : 'bg-secondary-subtle text-secondary';
    }

    public function textoUltimoAcerto(): string
    {
        if (! $this->ultimo_acerto_em) {
            return 'Sem registro';
        }

        return $this->ultimo_acerto_em->diffForHumans();
    }
}
