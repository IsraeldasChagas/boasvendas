<?php

namespace App\Services\Fiscal\Drivers;

use App\Models\FiscalNota;
use App\Services\Fiscal\EmissorInterface;
use App\Services\Fiscal\FiscalEmissaoResultado;

/**
 * Emissor próprio (serviço interno / microserviço) — estrutura para futura implementação.
 */
class EmissorProprioDriver implements EmissorInterface
{
    public function identificador(): string
    {
        return 'proprio';
    }

    public function emitir(FiscalNota $nota, array $payload): FiscalEmissaoResultado
    {
        return FiscalEmissaoResultado::naoImplementado($this->identificador(), $payload);
    }

    public function cancelar(FiscalNota $nota, string $motivo): FiscalEmissaoResultado
    {
        return FiscalEmissaoResultado::naoImplementado($this->identificador(), ['motivo' => $motivo]);
    }

    public function consultar(FiscalNota $nota): FiscalEmissaoResultado
    {
        return FiscalEmissaoResultado::naoImplementado($this->identificador(), ['chave' => $nota->chave_acesso]);
    }

    public function gerarDanfe(FiscalNota $nota): ?string
    {
        return null;
    }
}
