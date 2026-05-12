<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\FidelidadeCartao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteCartaoFidelidadeTest extends TestCase
{
    use RefreshDatabase;

    public function test_gerar_cartao_cria_codigo_vf_e_historico(): void
    {
        $empresa = Empresa::query()->create([
            'nome' => 'Loja Teste',
            'status' => 'ativa',
            'slug' => 'loja-teste-'.uniqid(),
        ]);
        $user = User::factory()->create([
            'empresa_id' => $empresa->id,
            'role' => 'operador',
        ]);
        $cliente = Cliente::query()->create([
            'empresa_id' => $empresa->id,
            'nome' => 'Maria',
            'telefone' => '(11) 98888-7777',
            'ativo' => true,
        ]);

        $this->actingAs($user)
            ->post(route('empresa.clientes.cartao-fidelidade.gerar', $cliente))
            ->assertRedirect(route('empresa.clientes.cartao-fidelidade.show', $cliente))
            ->assertSessionHas('status');

        $cartao = FidelidadeCartao::query()
            ->where('empresa_id', $empresa->id)
            ->where('cliente_id', $cliente->id)
            ->first();
        $this->assertNotNull($cartao);
        $this->assertMatchesRegularExpression('/^VF-\d{4}-\d{4}$/', (string) $cartao->codigo_fidelidade);
        $this->assertSame(FidelidadeCartao::STATUS_ATIVO, $cartao->status);
        $this->assertSame(1, $cartao->historicosPontos()->count());
    }

    public function test_nao_gera_segundo_cartao_ativo_mesmo_cliente(): void
    {
        $empresa = Empresa::query()->create([
            'nome' => 'Loja B',
            'status' => 'ativa',
            'slug' => 'loja-b-'.uniqid(),
        ]);
        $user = User::factory()->create(['empresa_id' => $empresa->id]);
        $cliente = Cliente::query()->create([
            'empresa_id' => $empresa->id,
            'nome' => 'João',
            'telefone' => '11977776666',
            'ativo' => true,
        ]);

        $this->actingAs($user)->post(route('empresa.clientes.cartao-fidelidade.gerar', $cliente));
        $this->actingAs($user)
            ->post(route('empresa.clientes.cartao-fidelidade.gerar', $cliente))
            ->assertSessionHas('warning');

        $this->assertSame(1, FidelidadeCartao::query()->where('cliente_id', $cliente->id)->where('status', FidelidadeCartao::STATUS_ATIVO)->count());
    }
}
