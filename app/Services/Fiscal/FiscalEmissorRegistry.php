<?php

namespace App\Services\Fiscal;

use App\Enums\Fiscal\FiscalEmissorDriver;
use App\Models\FiscalEmitente;
use App\Services\Fiscal\Drivers\EmissorProprioDriver;
use App\Services\Fiscal\Drivers\FocusNFeDriver;
use App\Services\Fiscal\Drivers\InternoDriver;
use App\Services\Fiscal\Drivers\NFeIoDriver;

/**
 * Resolve qual implementação de {@see EmissorInterface} usar conforme cadastro do emitente ou config global.
 */
final class FiscalEmissorRegistry
{
    public function resolve(?FiscalEmitente $emitente, ?FiscalEmissorDriver $driverPadrao): EmissorInterface
    {
        $raw = $emitente?->emissor_tipo ?? $driverPadrao?->value ?? FiscalEmissorDriver::Interno->value;
        $driver = FiscalEmissorDriver::tryFrom((string) $raw) ?? FiscalEmissorDriver::Interno;

        return match ($driver) {
            FiscalEmissorDriver::FocusNfe => app(FocusNFeDriver::class),
            FiscalEmissorDriver::NfeIo => app(NFeIoDriver::class),
            FiscalEmissorDriver::Proprio => app(EmissorProprioDriver::class),
            FiscalEmissorDriver::TecnoSpeed => app(NFeIoDriver::class),
            FiscalEmissorDriver::Interno => app(InternoDriver::class),
        };
    }
}
