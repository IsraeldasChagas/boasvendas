<?php

namespace App\Enums\Fiscal;

enum FiscalTipoDocumento: string
{
    case Nfce = 'nfce';
    case Nfe = 'nfe';
    case Nfse = 'nfse';

    public function rotulo(): string
    {
        return match ($this) {
            self::Nfce => 'NFC-e',
            self::Nfe => 'NF-e',
            self::Nfse => 'NFS-e',
        };
    }
}
