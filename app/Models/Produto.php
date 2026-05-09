<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Produto extends Model
{
    /** Na vitrine: cliente usa −/+ por ingrediente (quantidades). */
    public const ING_RETIRAR_UI_STEPPER = 'stepper';

    /** Na vitrine: cliente marca checkboxes (1 por ingrediente, até o máximo). */
    public const ING_RETIRAR_UI_CHECKBOX = 'checkbox';

    protected $table = 'produtos';

    protected $fillable = [
        'empresa_id',
        'categoria_id',
        'sku',
        'nome',
        'preco',
        'estoque',
        'descricao',
        'foto',
        'visivel_loja',
        'ativo',
        'permite_adicionais',
        'acrescimo_escolhas_min',
        'acrescimo_escolhas_max',
        'max_ingredientes_retirar',
        'ingredientes_retirar_ui',
    ];

    protected function casts(): array
    {
        return [
            'preco' => 'decimal:2',
            'estoque' => 'integer',
            'visivel_loja' => 'boolean',
            'ativo' => 'boolean',
            'permite_adicionais' => 'boolean',
        ];
    }

    /** Valor bruto do banco (nullable), sem cast para int que possa confundir null/0. */
    public function getAcrescimoEscolhasMinAttribute(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    public function getAcrescimoEscolhasMaxAttribute(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    public function setAcrescimoEscolhasMinAttribute(mixed $value): void
    {
        $this->attributes['acrescimo_escolhas_min'] = ($value === '' || $value === null) ? null : (int) $value;
    }

    public function setAcrescimoEscolhasMaxAttribute(mixed $value): void
    {
        $this->attributes['acrescimo_escolhas_max'] = ($value === '' || $value === null) ? null : (int) $value;
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function adicionais(): BelongsToMany
    {
        return $this->belongsToMany(Adicional::class, 'adicional_produto')->withTimestamps();
    }

    public function ingredientes(): HasMany
    {
        return $this->hasMany(ProdutoIngrediente::class, 'produto_id')->orderBy('ordem')->orderBy('nome');
    }

    /**
     * Caminho absoluto no disco do arquivo de foto (public/uploads ou storage/app/public).
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

    /**
     * URL da foto: sempre pela rota que lê o arquivo no disco (evita asset/realpath quebrando no Windows ou em subpastas).
     */
    public function urlFoto(): ?string
    {
        if ($this->resolveFotoAbsolutePath() === null) {
            return null;
        }

        $v = $this->updated_at?->getTimestamp() ?? time();

        return route('publico.produto_foto', ['produto' => $this->getKey()], absolute: false).'?v='.$v;
    }
}
