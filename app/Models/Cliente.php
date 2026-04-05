<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'empresa_id',
        'nome',
        'email',
        'telefone',
        'documento',
        'observacoes',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function rotuloContato(): string
    {
        $parts = array_filter([$this->telefone, $this->email]);

        return $parts !== [] ? implode(' · ', $parts) : '—';
    }
}
