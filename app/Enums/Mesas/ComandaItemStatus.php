<?php

namespace App\Enums\Mesas;

enum ComandaItemStatus: string
{
    case Pendente = 'pendente';
    case Enviado = 'enviado';
    case Recebido = 'recebido';
    case EmPreparo = 'em_preparo';
    case Pronto = 'pronto';
    case Entregue = 'entregue';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Enviado => 'Enviado',
            self::Recebido => 'Recebido',
            self::EmPreparo => 'Em preparo',
            self::Pronto => 'Pronto',
            self::Entregue => 'Entregue',
            self::Cancelado => 'Cancelado',
        };
    }
}
