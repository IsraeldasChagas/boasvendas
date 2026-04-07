<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

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

    public function urlFoto(): ?string
    {
        if ($this->foto === null || $this->foto === '') {
            return null;
        }

        return Storage::disk('public')->url($this->foto);
    }
}
