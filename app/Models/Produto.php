<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Produto extends Model
{
    /** Na vitrine: cliente usa −/+ por ingrediente (quantidades). */
    public const ING_RETIRAR_UI_STEPPER = 'stepper';

    /** Na vitrine: cliente marca checkboxes (1 por ingrediente, até o máximo). */
    public const ING_RETIRAR_UI_CHECKBOX = 'checkbox';

    /** Na vitrine: acréscimos pagos com −/+ por opção. */
    public const ACRESCIMO_LOJA_UI_STEPPER = 'stepper';

    /** Na vitrine: acréscimos pagos com uma caixa por opção (marcar se quer). */
    public const ACRESCIMO_LOJA_UI_CHECKBOX = 'checkbox';

    protected $table = 'produtos';

    protected $fillable = [
        'empresa_id',
        'categoria_id',
        'sku',
        'nome',
        'preco',
        'estoque',
        'controla_estoque',
        'descricao',
        'modo_preparo',
        'ficha_rendimento',
        'ficha_tempo_preparo_min',
        'foto',
        'visivel_loja',
        'ativo',
        'permite_adicionais',
        'acrescimo_escolhas_min',
        'acrescimo_escolhas_max',
        'max_ingredientes_retirar',
        'ingredientes_retirar_ui',
        'acrescimos_loja_ui',
        'fiscal_tipo_item',
        'fiscal_herdar_padrao',
        'fiscal_ncm',
        'fiscal_cfop',
        'fiscal_origem',
        'fiscal_unidade',
        'fiscal_csosn',
        'fiscal_cst',
        'fiscal_cest',
        'fiscal_gtin',
    ];

    protected function casts(): array
    {
        return [
            'preco' => 'decimal:2',
            'estoque' => 'integer',
            'controla_estoque' => 'boolean',
            'ficha_rendimento' => 'integer',
            'ficha_tempo_preparo_min' => 'integer',
            'visivel_loja' => 'boolean',
            'ativo' => 'boolean',
            'permite_adicionais' => 'boolean',
            'fiscal_herdar_padrao' => 'boolean',
            'fiscal_origem' => 'integer',
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

    public function estoqueMovimentos(): HasMany
    {
        return $this->hasMany(EstoqueMovimento::class, 'produto_id')->orderByDesc('id');
    }

    /** Ficha técnica (receita): insumos consumidos pela produção deste produto. */
    public function fichaTecnica(): HasMany
    {
        return $this->hasMany(ProdutoFichaItem::class, 'produto_id')
            ->with('insumo')
            ->orderBy('ordem')
            ->orderBy('id');
    }

    public function temFichaTecnica(): bool
    {
        if (! Schema::hasTable('produto_ficha_itens')) {
            return false;
        }

        return $this->relationLoaded('fichaTecnica')
            ? $this->fichaTecnica->isNotEmpty()
            : $this->fichaTecnica()->exists();
    }

    /** Porções que a ficha rende por produção (default 1). */
    public function fichaRendimento(): int
    {
        return max(1, (int) ($this->ficha_rendimento ?? 1));
    }

    /**
     * Quantas porções ainda dá para produzir com os insumos em estoque.
     * null = sem ficha técnica cadastrada (não há como calcular).
     */
    public function porcoesPossiveisPelaFicha(): ?int
    {
        if (! $this->temFichaTecnica()) {
            return null;
        }

        $itens = $this->relationLoaded('fichaTecnica') ? $this->fichaTecnica : $this->fichaTecnica()->get();

        $limites = $itens
            ->map(fn (ProdutoFichaItem $item) => $item->porcoesPossiveis())
            ->filter(fn (?int $v) => $v !== null);

        if ($limites->isEmpty()) {
            return null;
        }

        return (int) $limites->min() * $this->fichaRendimento();
    }

    /** Insumo que primeiro limita a produção (para avisar a cozinha). */
    public function insumoLimitanteDaFicha(): ?ProdutoFichaItem
    {
        if (! $this->temFichaTecnica()) {
            return null;
        }

        $itens = $this->relationLoaded('fichaTecnica') ? $this->fichaTecnica : $this->fichaTecnica()->get();

        return $itens
            ->filter(fn (ProdutoFichaItem $i) => $i->porcoesPossiveis() !== null)
            ->sortBy(fn (ProdutoFichaItem $i) => $i->porcoesPossiveis())
            ->first();
    }

    /** Produto com controle de saldo comercial (default true se coluna ausente). */
    public function controlaEstoque(): bool
    {
        if (! Schema::hasColumn('produtos', 'controla_estoque')) {
            return true;
        }

        return (bool) ($this->controla_estoque ?? true);
    }

    /**
     * Total permitido para retirada na loja (soma das quantidades nos ingredientes).
     * null ou 0 com ingredientes cadastrados: trata como “não configurado” e usa quantidade de ingredientes (até 99).
     * Muitos cadastros gravaram 0 por campo vazio / cast — não pode bloquear a vitrine nesses casos.
     */
    public function limiteRetiradaIngredientesNaLoja(): int
    {
        $n = $this->relationLoaded('ingredientes')
            ? $this->ingredientes->count()
            : (int) $this->ingredientes()->count();

        if ($n === 0) {
            return 0;
        }

        $m = $this->max_ingredientes_retirar;

        if ($m === null || (int) $m === 0) {
            return min(99, $n);
        }

        $mi = max(1, min((int) $m, 255));

        return min($mi, $n);
    }

    /**
     * Modo de UI na vitrine (stepper vs checkbox), normalizado — evita falha por espaços/caso no banco.
     */
    public function modoRetirarIngredientesNaLoja(): string
    {
        if (! Schema::hasColumn('produtos', 'ingredientes_retirar_ui')) {
            return self::ING_RETIRAR_UI_STEPPER;
        }

        $raw = $this->getAttributes()['ingredientes_retirar_ui'] ?? null;
        if ($raw === null || $raw === '') {
            return self::ING_RETIRAR_UI_STEPPER;
        }

        $v = strtolower(trim((string) $raw));

        return $v === self::ING_RETIRAR_UI_CHECKBOX
            ? self::ING_RETIRAR_UI_CHECKBOX
            : self::ING_RETIRAR_UI_STEPPER;
    }

    /** Como mostrar acréscimos pagos na loja (stepper ou checkbox). */
    public function modoAcrescimosNaLoja(): string
    {
        if (! Schema::hasColumn('produtos', 'acrescimos_loja_ui')) {
            return self::ACRESCIMO_LOJA_UI_STEPPER;
        }

        $raw = $this->getAttributes()['acrescimos_loja_ui'] ?? null;
        if ($raw === null || $raw === '') {
            return self::ACRESCIMO_LOJA_UI_STEPPER;
        }

        $v = strtolower(trim((string) $raw));

        return $v === self::ACRESCIMO_LOJA_UI_CHECKBOX
            ? self::ACRESCIMO_LOJA_UI_CHECKBOX
            : self::ACRESCIMO_LOJA_UI_STEPPER;
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

        return route('publico.produto_foto', ['produto' => $this->getKey()], absolute: true).'?v='.$v;
    }
}
