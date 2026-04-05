<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FidelidadeCartao extends Model
{
    protected $table = 'fidelidade_cartoes';

    protected $fillable = [
        'empresa_id',
        'telefone_normalizado',
        'cliente_id',
        'selos',
        'total_resgates',
    ];

    protected function casts(): array
    {
        return [
            'selos' => 'integer',
            'total_resgates' => 'integer',
        ];
    }

    public static function normalizarTelefone(?string $raw): string
    {
        return preg_replace('/\D+/', '', (string) $raw);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function telefoneMascarado(): string
    {
        $d = $this->telefone_normalizado;
        if (strlen($d) < 4) {
            return '***';
        }

        return '***'.substr($d, -4);
    }

    public function podeResgatar(FidelidadePrograma $programa): bool
    {
        return $programa->ativo && $this->selos >= $programa->pedidos_meta;
    }
}
