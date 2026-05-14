<?php

namespace App\Models;

use App\Enums\Mesas\ComandaStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comanda extends Model
{
    protected $table = 'comandas';

    protected $fillable = [
        'empresa_id',
        'unidade_id',
        'mesa_id',
        'cliente_nome',
        'cliente_documento',
        'garcom_id',
        'status',
        'subtotal',
        'taxa_servico',
        'desconto',
        'total',
        'taxa_servico_percentual',
        'observacao',
        'aberta_em',
        'fechada_em',
        'pedido_id',
        'integracao_payload',
    ];

    protected function casts(): array
    {
        return [
            'status' => ComandaStatus::class,
            'subtotal' => 'decimal:2',
            'taxa_servico' => 'decimal:2',
            'desconto' => 'decimal:2',
            'total' => 'decimal:2',
            'taxa_servico_percentual' => 'decimal:2',
            'aberta_em' => 'datetime',
            'fechada_em' => 'datetime',
            'integracao_payload' => 'array',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class, 'mesa_id');
    }

    public function garcom(): BelongsTo
    {
        return $this->belongsTo(User::class, 'garcom_id');
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ComandaItem::class, 'comanda_id');
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(PagamentoComanda::class, 'comanda_id');
    }

    public function scopeDaEmpresa($query, int $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    public function estaAbertaParaConsumo(): bool
    {
        return in_array($this->status, [
            ComandaStatus::Aberta,
            ComandaStatus::EmConsumo,
            ComandaStatus::ContaSolicitada,
        ], true);
    }
}
