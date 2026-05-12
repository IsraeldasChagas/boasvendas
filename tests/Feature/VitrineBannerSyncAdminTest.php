<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VitrineBannerSyncAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_e_redirecionado_ao_login_no_formulario(): void
    {
        $this->get(route('admin.empresas.vitrine-banner-sync.create'))
            ->assertRedirect(route('login'));
    }

    public function test_usuario_sem_acesso_master_recebe_403(): void
    {
        $user = User::factory()->create(['email' => 'equipe@exemplo.com']);

        $this->actingAs($user)
            ->get(route('admin.empresas.vitrine-banner-sync.create'))
            ->assertForbidden();
    }

    public function test_conta_master_pode_abrir_formulario_e_sincronizar_todas_as_outras(): void
    {
        $admin = User::query()->firstWhere('email', 'master@vendaffacil.com.br')
            ?? User::factory()->create(['email' => 'master@vendaffacil.com.br', 'name' => 'Master']);
        $origem = Empresa::query()->create([
            'nome' => 'Loja origem',
            'status' => 'ativa',
            'slug' => 'loja-origem-'.uniqid(),
        ]);
        Empresa::query()->create([
            'nome' => 'Loja destino',
            'status' => 'ativa',
            'slug' => 'loja-destino-'.uniqid(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.empresas.vitrine-banner-sync.create'))
            ->assertOk()
            ->assertSee('Sincronizar vitrine', false);

        $this->actingAs($admin)
            ->post(route('admin.empresas.vitrine-banner-sync.store'), [
                'origem_id' => $origem->id,
                'alvos' => 'todas',
            ])
            ->assertRedirect(route('admin.empresas.vitrine-banner-sync.create'))
            ->assertSessionHas('status');
    }
}
