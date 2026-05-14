<?php

namespace App\Enums\Mesas;

enum MesaStatus: string
{
    case Livre = 'livre';
    case Ocupada = 'ocupada';
    case AguardandoPedido = 'aguardando_pedido';
    case PedidoEnviado = 'pedido_enviado';
    case EmPreparo = 'em_preparo';
    case ContaSolicitada = 'conta_solicitada';
    case Fechada = 'fechada';

    public function label(): string
    {
        return match ($this) {
            self::Livre => 'Livre',
            self::Ocupada => 'Ocupada',
            self::AguardandoPedido => 'Aguardando pedido',
            self::PedidoEnviado => 'Pedido enviado',
            self::EmPreparo => 'Em preparo',
            self::ContaSolicitada => 'Conta solicitada',
            self::Fechada => 'Fechada',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
