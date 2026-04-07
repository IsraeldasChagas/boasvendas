<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Adicional extends Model
{
    public const TIPO_ACRESCENTAR = 'acrescentar';

    public const TIPO_RETIRAR = 'retirar';

    protected $table = 'adicionais';

    protected $fillable = [
        'empresa_id',
        'nome',
        'tipo',
        'preco',
        'ativo',
        'ordem',
    ];

    protected function casts(): array
    {
        return [
            'preco' => 'decimal:2',
            'ativo' => 'boolean',
            'ordem' => 'integer',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function produtos(): BelongsToMany
    {
        return $this->belongsToMany(Produto::class, 'adicional_produto')->withTimestamps();
    }

    /** @return array<string, string> */
    public static function tiposRotulos(): array
    {
        return [
            self::TIPO_ACRESCENTAR => 'Acrescentar (cobrar)',
            self::TIPO_RETIRAR => 'Retirar ingrediente (sem custo)',
        ];
    }

    public function rotuloTipo(): string
    {
        return self::tiposRotulos()[$this->tipo] ?? $this->tipo;
    }
}
