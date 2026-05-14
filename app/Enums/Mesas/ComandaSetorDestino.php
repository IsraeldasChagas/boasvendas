<?php

namespace App\Enums\Mesas;

enum ComandaSetorDestino: string
{
    case Cozinha = 'cozinha';
    case Bar = 'bar';
    case Caixa = 'caixa';

    public function label(): string
    {
        return match ($this) {
            self::Cozinha => 'Cozinha',
            self::Bar => 'Bar',
            self::Caixa => 'Caixa',
        };
    }
}
