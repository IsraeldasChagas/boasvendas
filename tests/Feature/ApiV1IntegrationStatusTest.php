<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\EmpresaApiToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiV1IntegrationStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_requer_bearer_token(): void
    {
        $this->getJson('/api/v1/integration/status')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'code' => 'api.unauthenticated',
            ]);
    }

    public function test_status_com_token_valido(): void
    {
        $empresa = Empresa::query()->create([
            'nome' => 'Empresa API Teste',
            'status' => 'ativa',
            'slug' => 'empresa-api-'.uniqid(),
        ]);

        $issued = EmpresaApiToken::issue(
            $empresa,
            'Teste integração',
            ['api.visualizar'],
            EmpresaApiToken::ENV_HOMOLOGACAO,
        );

        $this->withToken($issued['plain'])
            ->getJson('/api/v1/integration/status')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('system', 'VendaFácil')
            ->assertJsonPath('api_version', '1.0')
            ->assertJsonPath('company.id', (string) $empresa->id)
            ->assertJsonPath('company.name', 'Empresa API Teste')
            ->assertJsonPath('environment', 'homologacao');
    }

    public function test_status_nega_sem_ability(): void
    {
        $empresa = Empresa::query()->create([
            'nome' => 'Empresa Sem Ability',
            'status' => 'ativa',
            'slug' => 'empresa-sem-ability-'.uniqid(),
        ]);

        $issued = EmpresaApiToken::issue(
            $empresa,
            'Token restrito',
            ['pedidos.api.consultar'],
            EmpresaApiToken::ENV_HOMOLOGACAO,
        );

        $this->withToken($issued['plain'])
            ->getJson('/api/v1/integration/status')
            ->assertStatus(403)
            ->assertJsonPath('code', 'api.forbidden');
    }

    public function test_token_revogado_retorna_401(): void
    {
        $empresa = Empresa::query()->create([
            'nome' => 'Empresa Revogada',
            'status' => 'ativa',
            'slug' => 'empresa-rev-'.uniqid(),
        ]);

        $issued = EmpresaApiToken::issue(
            $empresa,
            'Token revogar',
            ['api.visualizar'],
        );
        $issued['model']->revoke();

        $this->withToken($issued['plain'])
            ->getJson('/api/v1/integration/status')
            ->assertStatus(401)
            ->assertJsonPath('code', 'api.invalid_token');
    }
}
