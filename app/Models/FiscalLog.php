<?php

namespace App\Models;

use App\Enums\Fiscal\FiscalLogTipo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalLog extends Model
{
    protected $table = 'fiscal_logs';

    protected $fillable = [
        'empresa_id',
        'nota_id',
        'tipo',
        'mensagem',
        'payload',
        'retorno',
        'unidade_id',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => FiscalLogTipo::class,
            'payload' => 'array',
            'retorno' => 'array',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function nota(): BelongsTo
    {
        return $this->belongsTo(FiscalNota::class, 'nota_id');
    }
}
