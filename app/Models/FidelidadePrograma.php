<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FidelidadePrograma extends Model
{
    protected $table = 'fidelidade_programas';

    public const TIPO_PRODUTO = 'produto';

    public const TIPO_DESCONTO_VALOR = 'desconto_valor';

    protected $fillable = [
        'empresa_id',
        'ativo',
        'nome_exibicao',
        'pedidos_meta',
        'tipo_recompensa',
        'produto_id',
        'valor_desconto',
        'texto_recompensa',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'pedidos_meta' => 'integer',
            'valor_desconto' => 'decimal:2',
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

    public function resumoRecompensa(): string
    {
        if ($this->tipo_recompensa === self::TIPO_PRODUTO && $this->produto) {
            return $this->produto->nome;
        }

        if ($this->tipo_recompensa === self::TIPO_DESCONTO_VALOR && $this->valor_desconto !== null) {
            return 'R$ '.number_format((float) $this->valor_desconto, 2, ',', '.').' de desconto';
        }

        return 'Recompensa configurada pela loja';
    }
}
