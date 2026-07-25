<?php

namespace App\Models;

use App\Enums\Estoque\EstoqueMovimentoTipo;
use App\Enums\Estoque\UnidadeMedida;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EstoqueMovimento extends Model
{
    protected $table = 'estoque_movimentos';

    protected $fillable = [
        'empresa_id',
        'produto_id',
        'insumo_id',
        'tipo',
        'delta',
        'saldo_apos',
        'unidade',
        'referencia_type',
        'referencia_id',
        'observacao',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => EstoqueMovimentoTipo::class,
            'delta' => 'float',
            'saldo_apos' => 'float',
            'unidade' => UnidadeMedida::class,
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

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class, 'insumo_id');
    }

    /** Movimento legível: produto em unidades, insumo na unidade base. */
    public function deltaFormatado(): string
    {
        $delta = (float) $this->delta;
        $sinal = $delta > 0 ? '+' : '−';

        if ($this->insumo_id !== null && $this->unidade !== null) {
            return $sinal.UnidadeMedida::formatar(abs($delta), $this->unidade);
        }

        return $sinal.(int) abs($delta);
    }

    public function saldoAposFormatado(): string
    {
        if ($this->insumo_id !== null && $this->unidade !== null) {
            return UnidadeMedida::formatar((float) $this->saldo_apos, $this->unidade);
        }

        return (string) (int) $this->saldo_apos;
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
