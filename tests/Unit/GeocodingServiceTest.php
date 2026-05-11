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

    public function test_montar_query_cep_com_uf_sem_rua_usa_so_cep(): void
    {
        $g = new GeocodingService;
        $q = $g->montarQueryCliente([
            'cep' => '76801-192',
            'estado' => 'RO',
            'cidade' => 'Porto Velho',
            'numero' => '10',
        ]);
        $this->assertSame('76801-192, Brasil', $q);
    }

    public function test_montar_query_rua_curta_com_cep_usa_so_cep(): void
    {
        $g = new GeocodingService;
        $q = $g->montarQueryCliente([
            'cep' => '76801-010',
            'rua' => 'Ru',
            'cidade' => 'Porto Velho',
        ]);
        $this->assertSame('76801-010, Brasil', $q);
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

    public function test_montar_query_rua_tres_caracteres_com_cep_monta_endereco(): void
    {
        $g = new GeocodingService;
        $q = $g->montarQueryCliente([
            'cep' => '76801-010',
            'rua' => 'Rua',
            'cidade' => 'Porto Velho',
            'estado' => 'RO',
        ]);
        $this->assertStringContainsString('Rua', $q);
        $this->assertStringContainsString('Porto Velho - RO', $q);
        $this->assertStringContainsString('76801-010', $q);
    }
}
