<?php

namespace App\Models;

use App\Enums\Estoque\UnidadeMedida;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Insumo/ingrediente com estoque próprio, sempre em unidade base (g, ml, un).
 */
class Insumo extends Model
{
    protected $table = 'insumos';

    protected $fillable = [
        'empresa_id',
        'nome',
        'foto',
        'unidade_base',
        'saldo',
        'estoque_minimo',
        'custo_unitario',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'unidade_base' => UnidadeMedida::class,
            'saldo' => 'float',
            'estoque_minimo' => 'float',
            'custo_unitario' => 'float',
            'ativo' => 'boolean',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function fichaItens(): HasMany
    {
        return $this->hasMany(ProdutoFichaItem::class, 'insumo_id');
    }

    public function movimentos(): HasMany
    {
        return $this->hasMany(EstoqueMovimento::class, 'insumo_id')->orderByDesc('id');
    }

    public function unidadeBase(): UnidadeMedida
    {
        return $this->unidade_base ?? UnidadeMedida::Grama;
    }

    /** Saldo legível ("1,5 kg" em vez de "1500 g"). */
    public function saldoFormatado(): string
    {
        return UnidadeMedida::formatar((float) $this->saldo, $this->unidadeBase());
    }

    public function abaixoDoMinimo(): bool
    {
        return (float) $this->estoque_minimo > 0 && (float) $this->saldo <= (float) $this->estoque_minimo;
    }

    public function urlFoto(): ?string
    {
        if (! filled($this->foto)) {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', (string) $this->foto), '/');
        if ($path === '' || ! Storage::disk('uploads')->exists($path)) {
            return null;
        }

        // Disco `uploads` aponta para public/uploads — não usar Storage::url (aponta para /storage).
        $v = $this->updated_at?->getTimestamp() ?? time();

        return asset('uploads/'.$path).'?v='.$v;
    }
}
