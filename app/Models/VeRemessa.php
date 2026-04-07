<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VeRemessa extends Model
{
    protected $table = 've_remessas';

    public const STATUS_PREPARACAO = 'preparacao';

    public const STATUS_EM_CAMPO = 'em_campo';

    public const STATUS_ENCERRADA = 'encerrada';

    protected $fillable = [
        'empresa_id',
        've_ponto_id',
        'produto_id',
        'titulo',
        'status',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function ponto(): BelongsTo
    {
        return $this->belongsTo(VePonto::class, 've_ponto_id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    public function acertos(): HasMany
    {
        return $this->hasMany(VeAcerto::class, 've_remessa_id');
    }

    public function estaAcertada(): bool
    {
        return $this->acertos()->where('status', VeAcerto::STATUS_CONCLUIDO)->exists();
    }

    public static function rotulosStatus(): array
    {
        return [
            self::STATUS_PREPARACAO => 'Preparação',
            self::STATUS_EM_CAMPO => 'Em campo',
            self::STATUS_ENCERRADA => 'Encerrada',
        ];
    }

    public function rotuloStatus(): string
    {
        return self::rotulosStatus()[$this->status] ?? $this->status;
    }

    public function classeBadgeStatus(): string
    {
        return match ($this->status) {
            self::STATUS_EM_CAMPO => 'bg-warning-subtle text-warning',
            self::STATUS_ENCERRADA => 'bg-success-subtle text-success',
            default => 'bg-secondary-subtle text-secondary',
        };
    }

    public function tituloExibicao(): string
    {
        return $this->titulo ?: 'Remessa #'.$this->id;
    }
}
