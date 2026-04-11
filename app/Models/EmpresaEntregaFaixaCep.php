<?php

namespace App\Models;

use App\Support\Cep;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpresaEntregaFaixaCep extends Model
{
    protected $table = 'empresa_entrega_faixas_cep';

    protected $fillable = [
        'empresa_id',
        'cep_inicio',
        'cep_fim',
        'valor_taxa',
        'nome_regiao',
    ];

    protected function casts(): array
    {
        return [
            'valor_taxa' => 'decimal:2',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /** Valor da taxa se o CEP cair na faixa; null se não houver faixa. */
    public static function taxaParaCep(int $empresaId, string $cep8): ?float
    {
        $row = self::query()
            ->where('empresa_id', $empresaId)
            ->where('cep_inicio', '<=', $cep8)
            ->where('cep_fim', '>=', $cep8)
            ->orderBy('id')
            ->first();

        return $row ? (float) $row->valor_taxa : null;
    }

    public static function normalizarLimite(?string $cep): ?string
    {
        return Cep::normalizar8($cep);
    }
}
