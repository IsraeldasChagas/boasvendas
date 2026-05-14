<?php

namespace App\Enums\Fiscal;

enum FiscalAmbiente: string
{
    case Homologacao = 'homologacao';
    case Producao = 'producao';

    public function rotulo(): string
    {
        return match ($this) {
            self::Homologacao => 'Homologação',
            self::Producao => 'Produção',
        };
    }
}
