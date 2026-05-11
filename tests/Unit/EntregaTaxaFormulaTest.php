<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Regra: taxa_base + ceil(max(0, km - km_incluso)) * valor_km_extra
 */
class EntregaTaxaFormulaTest extends TestCase
{
    public function test_exemplo_enunciado_54_km(): void
    {
        $taxaBase = 5.0;
        $kmIncluso = 3.0;
        $valorKmExtra = 2.0;
        $distKm = 5.4;
        $extraKm = max(0.0, $distKm - $kmIncluso);
        $unidades = (int) ceil($extraKm);
        $taxa = $taxaBase + $unidades * $valorKmExtra;
        $this->assertSame(3, $unidades);
        $this->assertSame(11.0, $taxa);
    }
}
