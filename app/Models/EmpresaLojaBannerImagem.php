<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EmpresaLojaBannerImagem extends Model
{
    protected $table = 'empresa_loja_banner_imagens';

    protected $fillable = [
        'empresa_id',
        'caminho',
        'ordem',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function resolveAbsolutePath(): ?string
    {
        $rel = ltrim(str_replace('\\', '/', (string) $this->caminho), '/');
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

    public function urlPublica(): ?string
    {
        if ($this->resolveAbsolutePath() === null) {
            return null;
        }

        $v = $this->updated_at?->getTimestamp() ?? time();

        return route('publico.empresa_loja_banner_imagem', [
            'empresa' => $this->empresa_id,
            'bannerImagem' => $this->getKey(),
        ], absolute: false).'?v='.$v;
    }
}
