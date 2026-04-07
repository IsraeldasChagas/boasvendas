<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    protected $table = 'pedidos';

    public const CANAL_LOJA = 'loja';

    public const STATUS_RECEBIDO = 'recebido';

    public const STATUS_PREPARO = 'preparo';

    public const STATUS_PRONTO = 'pronto';

    public const STATUS_ROTA = 'rota';

    public const STATUS_ENTREGUE = 'entregue';

    public const STATUS_CANCELADO = 'cancelado';

    public const PAGAMENTO_PIX = 'pix';

    /** @deprecated Pedidos antigos; exibir como cartão genérico */
    public const PAGAMENTO_CARTAO = 'cartao';

    public const PAGAMENTO_CARTAO_CREDITO_MAQUININHA = 'cartao_credito';

    public const PAGAMENTO_CARTAO_DEBITO_MAQUININHA = 'cartao_debito';

    public const PAGAMENTO_DINHEIRO = 'dinheiro';

    public const PAGAMENTO_ENTREGA = 'entrega';

    protected $fillable = [
        'empresa_id',
        'codigo_publico',
        'canal',
        'cliente_nome',
        'cliente_telefone',
        'cliente_email',
        'endereco',
        'complemento',
        'forma_pagamento',
        'pagamento_troco_para',
        'observacoes',
        'status',
        'subtotal',
        'taxa_entrega',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'taxa_entrega' => 'decimal:2',
            'total' => 'decimal:2',
            'pagamento_troco_para' => 'decimal:2',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(PedidoItem::class, 'pedido_id');
    }

    /** @return array<string, string> */
    public static function statusRotulos(): array
    {
        return [
            self::STATUS_RECEBIDO => 'Recebido',
            self::STATUS_PREPARO => 'Em preparo',
            self::STATUS_PRONTO => 'Pronto',
            self::STATUS_ROTA => 'Em rota',
            self::STATUS_ENTREGUE => 'Entregue',
            self::STATUS_CANCELADO => 'Cancelado',
        ];
    }

    public function rotuloStatus(): string
    {
        return self::statusRotulos()[$this->status] ?? $this->status;
    }

    public function classeBadgeStatus(): string
    {
        return match ($this->status) {
            self::STATUS_ENTREGUE => 'bg-success-subtle text-success',
            self::STATUS_CANCELADO => 'bg-secondary-subtle text-secondary',
            self::STATUS_ROTA, self::STATUS_PRONTO => 'bg-warning-subtle text-warning',
            self::STATUS_PREPARO => 'bg-info-subtle text-info',
            default => 'bg-primary-subtle text-primary',
        };
    }

    /** @return array<string, string> */
    public static function formasPagamentoRotulos(): array
    {
        return [
            self::PAGAMENTO_PIX => 'PIX',
            self::PAGAMENTO_CARTAO_CREDITO_MAQUININHA => 'Cartão de crédito (na maquininha)',
            self::PAGAMENTO_CARTAO_DEBITO_MAQUININHA => 'Cartão de débito (na maquininha)',
            self::PAGAMENTO_DINHEIRO => 'Dinheiro',
            self::PAGAMENTO_ENTREGA => 'Na entrega (combinar)',
            self::PAGAMENTO_CARTAO => 'Cartão',
        ];
    }

    public function rotuloFormaPagamento(): string
    {
        return self::formasPagamentoRotulos()[$this->forma_pagamento] ?? $this->forma_pagamento;
    }

    /** Texto para exibição ao cliente e na empresa (forma + troco em dinheiro). */
    public function descricaoPagamentoExibicao(): string
    {
        $rotulo = $this->rotuloFormaPagamento();

        if ($this->forma_pagamento !== self::PAGAMENTO_DINHEIRO) {
            return $rotulo;
        }

        $pagaCom = $this->pagamento_troco_para;
        if ($pagaCom === null) {
            return $rotulo.' — valor exato ou troco não informado';
        }

        $paga = (float) $pagaCom;
        $total = (float) $this->total;
        $troco = round(max(0, $paga - $total), 2);

        return $rotulo.' — paga com R$ '.number_format($paga, 2, ',', '.').' (troco R$ '.number_format($troco, 2, ',', '.').')';
    }
}
