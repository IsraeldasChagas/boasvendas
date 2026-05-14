<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MesaConfiguracao extends Model
{
    protected $table = 'mesa_configuracoes';

    protected $fillable = [
        'empresa_id',
        'unidade_id',
        'taxa_servico_padrao_percent',
        'exigir_garcom_abertura',
    ];

    protected function casts(): array
    {
        return [
            'taxa_servico_padrao_percent' => 'decimal:2',
            'exigir_garcom_abertura' => 'boolean',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public static function obterOuCriarPadrao(int $empresaId, ?int $unidadeId = null): self
    {
        return self::query()->firstOrCreate(
            ['empresa_id' => $empresaId, 'unidade_id' => $unidadeId],
            [
                'taxa_servico_padrao_percent' => 10,
                'exigir_garcom_abertura' => false,
            ]
        );
    }
}
