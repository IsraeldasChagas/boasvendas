<?php

namespace Tests\Unit;

use App\Services\GeocodingService;
use Tests\TestCase;

class GeocodingServiceTest extends TestCase
{
    public function test_montar_query_so_cep(): void
    {
        $g = new GeocodingService;
        $q = $g->montarQueryCliente(['cep' => '76800-000']);
        $this->assertSame('76800-000, Brasil', $q);
    }

    public function test_montar_query_endereco_completo(): void
    {
        $g = new GeocodingService;
        $q = $g->montarQueryCliente([
            'cep' => '76801-010',
            'rua' => 'Rua Teste',
            'numero' => '100',
            'bairro' => 'Centro',
            'cidade' => 'Porto Velho',
            'estado' => 'RO',
        ]);
        $this->assertStringContainsString('Rua Teste, 100', $q);
        $this->assertStringContainsString('Centro', $q);
        $this->assertStringContainsString('Porto Velho - RO', $q);
        $this->assertStringContainsString('76801-010', $q);
        $this->assertStringContainsString('Brasil', $q);
    }
}
