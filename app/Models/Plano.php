<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plano extends Model
{
    protected $table = 'planos';

    protected $fillable = [
        'nome',
        'preco_mensal',
        'feature_primaria',
        'feature_secundaria',
        'ordem',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'preco_mensal' => 'decimal:2',
            'ativo' => 'boolean',
            'ordem' => 'integer',
        ];
    }

    public function assinaturas(): HasMany
    {
        return $this->hasMany(Assinatura::class, 'plano_id');
    }

    public function empresas(): HasMany
    {
        return $this->hasMany(Empresa::class, 'plano_id');
    }
}
