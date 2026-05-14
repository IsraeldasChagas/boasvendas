<?php

namespace App\Enums\Mesas;

enum PagamentoComandaStatus: string
{
    case Pendente = 'pendente';
    case Confirmado = 'confirmado';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Confirmado => 'Confirmado',
            self::Cancelado => 'Cancelado',
        };
    }
}
