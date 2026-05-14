<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
        'foto',
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

    /**
     * Adicionais de acréscimo ligados a pelo menos um produto visível na loja (com personalização).
     * Se $categoriaId for informado, só entram adicionais usados por produtos dessa categoria.
     *
     * @return Collection<int, self>
     */
    public static function adicionaisAvulsosCatalogoParaLoja(int $empresaId, ?int $categoriaId): Collection
    {
        $q = static::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->where('tipo', self::TIPO_ACRESCENTAR)
            ->whereHas('produtos', function ($pq) use ($empresaId, $categoriaId) {
                $pq->where('produtos.empresa_id', $empresaId)
                    ->where('produtos.ativo', true)
                    ->where('produtos.visivel_loja', true)
                    ->where('produtos.permite_adicionais', true);
                if ($categoriaId !== null && $categoriaId > 0) {
                    $pq->where('produtos.categoria_id', $categoriaId);
                }
            })
            ->orderBy('ordem')
            ->orderBy('nome');

        return $q->get()->unique('id')->values();
    }

    /**
     * Pode ser vendido avulso na vitrine (ligado a produto elegível). $contextoCategoriaId opcional restringe à categoria.
     */
    public static function permiteCompraAvulsaNaLoja(int $empresaId, int $adicionalId, ?int $contextoCategoriaId): bool
    {
        return static::query()
            ->where('id', $adicionalId)
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->where('tipo', self::TIPO_ACRESCENTAR)
            ->whereHas('produtos', function ($pq) use ($empresaId, $contextoCategoriaId) {
                $pq->where('produtos.empresa_id', $empresaId)
                    ->where('produtos.ativo', true)
                    ->where('produtos.visivel_loja', true)
                    ->where('produtos.permite_adicionais', true);
                if ($contextoCategoriaId !== null && $contextoCategoriaId > 0) {
                    $pq->where('produtos.categoria_id', $contextoCategoriaId);
                }
            })
            ->exists();
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

    /**
     * Caminho absoluto no disco (public/uploads ou storage/app/public legado).
     */
    public function resolveFotoAbsolutePath(): ?string
    {
        if ($this->foto === null || $this->foto === '') {
            return null;
        }

        $rel = ltrim(str_replace('\\', '/', (string) $this->foto), '/');
        if ($rel === '' || Str::contains($rel, '..')) {
            return null;
        }

        $candidates = [
            public_path('uploads/'.$rel),
            public_path($rel),
        ];

        if (Str::startsWith($rel, 'uploads/')) {
            $candidates[] = public_path($rel);
            $candidates[] = public_path('uploads/'.ltrim(Str::after($rel, 'uploads/'), '/'));
        }

        foreach (array_unique(array_filter($candidates)) as $full) {
            if (@is_file($full)) {
                return $full;
            }
        }

        $storage = storage_path('app/public/'.$rel);
        if (@is_file($storage)) {
            return $storage;
        }

        return null;
    }

    public function urlFoto(): ?string
    {
        if ($this->resolveFotoAbsolutePath() === null) {
            return null;
        }

        $v = $this->updated_at?->getTimestamp() ?? time();

        return route('publico.adicional_foto', ['adicionalId' => $this->getKey()], absolute: false).'?v='.$v;
    }
}
