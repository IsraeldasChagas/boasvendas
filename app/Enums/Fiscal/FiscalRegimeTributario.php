<?php

namespace App\Enums\Fiscal;

enum FiscalRegimeTributario: string
{
    case SimplesNacional = '1';
    case SimplesExcessoSublimite = '2';
    case RegimeNormal = '3';
    case Mei = '4';

    public function rotulo(): string
    {
        return match ($this) {
            self::SimplesNacional => '1 — Simples Nacional',
            self::SimplesExcessoSublimite => '2 — Simples Nacional (excesso de sublimite)',
            self::RegimeNormal => '3 — Regime normal',
            self::Mei => '4 — MEI',
        };
    }
}
