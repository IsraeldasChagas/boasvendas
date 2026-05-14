<?php

namespace App\Services\Fiscal;

/**
 * Resultado padronizado de chamadas ao emissor (API externa ou próprio).
 * Mantido como DTO simples para evoluir sem acoplar a modelos Eloquent.
 */
final class FiscalEmissaoResultado
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $retorno
     */
    public function __construct(
        public bool $ok,
        public string $mensagem,
        public array $payload = [],
        public array $retorno = [],
    ) {}

    /** @param  array<string, mixed>  $payload */
    public static function naoImplementado(string $driver, array $payload = []): self
    {
        return new self(
            false,
            'Integração fiscal em modo estrutural: o driver «'.$driver.'» ainda não executa chamadas reais à API/SEFAZ.',
            $payload,
            ['simulado' => true, 'driver' => $driver],
        );
    }
}
