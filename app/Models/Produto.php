<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produto extends Model
{
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
        'max_ingredientes_retirar',
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
     * URL da foto: prioriza arquivo em public/uploads (hospedagem sem storage:link);
     * se ainda estiver só em storage/app/public, usa rota que envia o arquivo via PHP.
     */
    public function urlFoto(): ?string
    {
        if ($this->foto === null || $this->foto === '') {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', $this->foto), '/');

        if (is_file(public_path('uploads/'.$path))) {
            return asset('uploads/'.$path);
        }

        if (is_file(storage_path('app/public/'.$path))) {
            return '/media/produto/'.$this->id;
        }

        return null;
    }
}
