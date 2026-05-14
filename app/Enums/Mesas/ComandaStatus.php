<?php

namespace App\Enums\Mesas;

enum ComandaStatus: string
{
    case Aberta = 'aberta';
    case EmConsumo = 'em_consumo';
    case ContaSolicitada = 'conta_solicitada';
    case Fechada = 'fechada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Aberta => 'Aberta',
            self::EmConsumo => 'Em consumo',
            self::ContaSolicitada => 'Conta solicitada',
            self::Fechada => 'Fechada',
            self::Cancelada => 'Cancelada',
        };
    }

    /** @return list<string> */
    public static function abertasValues(): array
    {
        return [
            self::Aberta->value,
            self::EmConsumo->value,
            self::ContaSolicitada->value,
        ];
    }
}
