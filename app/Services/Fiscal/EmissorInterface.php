<?php

namespace App\Services\Fiscal;

use App\Models\FiscalNota;

/**
 * Contrato único para emissores fiscais (Focus, NFE.io, TecnoSpeed, emissor próprio, etc.).
 * Implementações concretas ficam em {@see \App\Services\Fiscal\Drivers}.
 */
interface EmissorInterface
{
    public function identificador(): string;

    /** Disparo de emissão (NFC-e / NF-e / NFS-e conforme payload). */
    public function emitir(FiscalNota $nota, array $payload): FiscalEmissaoResultado;

    public function cancelar(FiscalNota $nota, string $motivo): FiscalEmissaoResultado;

    public function consultar(FiscalNota $nota): FiscalEmissaoResultado;

    /** Retorna caminho relativo ao disco de armazenamento ou URL pública, conforme driver. */
    public function gerarDanfe(FiscalNota $nota): ?string;
}
