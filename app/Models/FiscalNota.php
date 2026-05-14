<?php

namespace App\Models;

use App\Enums\Fiscal\FiscalAmbiente;
use App\Enums\Fiscal\FiscalNotaStatus;
use App\Enums\Fiscal\FiscalTipoDocumento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FiscalNota extends Model
{
    protected $table = 'fiscal_notas';

    protected $fillable = [
        'pedido_id',
        'empresa_id',
        'cliente_id',
        'tipo_nota',
        'numero_nota',
        'serie',
        'chave_acesso',
        'protocolo',
        'status',
        'xml_path',
        'danfe_path',
        'motivo_rejeicao',
        'data_emissao',
        'valor_total',
        'ambiente',
        'payload_json',
        'retorno_json',
        'unidade_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => FiscalNotaStatus::class,
            'tipo_nota' => FiscalTipoDocumento::class,
            'ambiente' => FiscalAmbiente::class,
            'data_emissao' => 'datetime',
            'valor_total' => 'decimal:2',
            'payload_json' => 'array',
            'retorno_json' => 'array',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(FiscalLog::class, 'nota_id')->orderByDesc('id');
    }
}
