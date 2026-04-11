<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaixaMovimento extends Model
{
    protected $table = 'caixa_movimentos';

    public const TIPO_SUPRIMENTO = 'suprimento';

    public const TIPO_SANGRIA = 'sangria';

    public const TIPO_VENDA_AVULSA = 'venda_avulsa';

    public const TIPO_ENTRADA_MANUAL = 'entrada_manual';

    public const TIPO_SAIDA_MANUAL = 'saida_manual';

    protected $fillable = [
        'caixa_turno_id',
        'user_id',
        'tipo',
        'descricao',
        'valor',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
        ];
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(CaixaTurno::class, 'caixa_turno_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isEntrada(): bool
    {
        return in_array($this->tipo, [
            self::TIPO_SUPRIMENTO,
            self::TIPO_VENDA_AVULSA,
            self::TIPO_ENTRADA_MANUAL,
        ], true);
    }

    public static function rotuloTipo(string $tipo): string
    {
        return match ($tipo) {
            self::TIPO_SUPRIMENTO => 'Suprimento',
            self::TIPO_SANGRIA => 'Sangria',
            self::TIPO_VENDA_AVULSA => 'Venda (dinheiro)',
            self::TIPO_ENTRADA_MANUAL => 'Entrada manual',
            self::TIPO_SAIDA_MANUAL => 'Saída manual',
            default => $tipo,
        };
    }
}
