<?php

namespace App\Enums\Fiscal;

/** Driver / emissor configurado em fiscal_empresas.emissor_tipo (extensível). */
enum FiscalEmissorDriver: string
{
    case Interno = 'interno';
    case FocusNfe = 'focus_nfe';
    case NfeIo = 'nfe_io';
    case TecnoSpeed = 'tecnospeed';
    case Proprio = 'proprio';

    public function rotulo(): string
    {
        return match ($this) {
            self::Interno => 'Nenhum (estrutura apenas)',
            self::FocusNfe => 'Focus NFe',
            self::NfeIo => 'NFE.io',
            self::TecnoSpeed => 'TecnoSpeed',
            self::Proprio => 'Emissor próprio',
        };
    }
}
