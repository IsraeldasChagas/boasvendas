<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FidelidadePontosHistorico extends Model
{
    protected $table = 'fidelidade_pontos_historicos';

    public const TIPO_GERACAO = 'geracao';

    public const TIPO_SELO = 'selo';

    public const TIPO_RESGATE = 'resgate';

    public const TIPO_AJUSTE = 'ajuste';

    public const TIPO_STATUS = 'status';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'cartao_fidelidade_id',
        'tipo_movimento',
        'pontos',
        'descricao',
    ];

    protected function casts(): array
    {
        return [
            'pontos' => 'integer',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function cartao(): BelongsTo
    {
        return $this->belongsTo(FidelidadeCartao::class, 'cartao_fidelidade_id');
    }
}
