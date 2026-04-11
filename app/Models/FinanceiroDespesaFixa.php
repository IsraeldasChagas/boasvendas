<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceiroDespesaFixa extends Model
{
    protected $table = 'financeiro_despesas_fixas';

    protected $fillable = [
        'empresa_id',
        'nome',
        'valor_mensal',
        'categoria',
        'observacoes',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'valor_mensal' => 'decimal:2',
            'ativo' => 'boolean',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
