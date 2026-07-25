<?php

namespace App\Models;

use App\Enums\Estoque\EstoqueMovimentoTipo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EstoqueMovimento extends Model
{
    protected $table = 'estoque_movimentos';

    protected $fillable = [
        'empresa_id',
        'produto_id',
        'tipo',
        'delta',
        'saldo_apos',
        'referencia_type',
        'referencia_id',
        'observacao',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => EstoqueMovimentoTipo::class,
            'delta' => 'integer',
            'saldo_apos' => 'integer',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function referencia(): MorphTo
    {
        return $this->morphTo();
    }
}
