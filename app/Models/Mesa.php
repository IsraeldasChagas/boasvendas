<?php

namespace App\Models;

use App\Enums\Mesas\ComandaStatus;
use App\Enums\Mesas\MesaStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Mesa extends Model
{
    protected $table = 'mesas';

    protected $fillable = [
        'empresa_id',
        'unidade_id',
        'numero',
        'nome',
        'capacidade',
        'localizacao',
        'status',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'status' => MesaStatus::class,
            'ativo' => 'boolean',
            'capacidade' => 'integer',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function comandas(): HasMany
    {
        return $this->hasMany(Comanda::class, 'mesa_id');
    }

    /** Comanda ainda não encerrada (aberta, consumo ou conta). */
    public function comandaAberta(): HasOne
    {
        return $this->hasOne(Comanda::class, 'mesa_id')
            ->whereIn('status', ComandaStatus::abertasValues());
    }

    public function scopeDaEmpresa($query, int $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    public function scopeAtivas($query)
    {
        return $query->where('ativo', true);
    }
}
