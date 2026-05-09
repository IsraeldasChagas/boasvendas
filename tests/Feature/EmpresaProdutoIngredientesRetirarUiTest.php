<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Produto;
use App\Models\ProdutoIngrediente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmpresaProdutoIngredientesRetirarUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_atualizar_produto_persiste_ingredientes_retirar_ui_checkbox(): void
    {
        if (! Schema::hasColumn('produtos', 'ingredientes_retirar_ui')) {
            $this->markTestSkipped('Coluna ingredientes_retirar_ui indisponível nesta base de testes.');
        }

        $empresa = Empresa::query()->create([
            'nome' => 'Empresa Teste',
            'status' => 'ativa',
        ]);

        $user = User::factory()->create([
            'empresa_id' => $empresa->id,
            'role' => 'operador',
        ]);

        $produto = Produto::query()->create([
            'empresa_id' => $empresa->id,
            'sku' => 'SKU-T-001',
            'nome' => 'Suco teste',
            'preco' => 9.90,
            'estoque' => 10,
            'descricao' => null,
            'visivel_loja' => true,
            'ativo' => true,
            'permite_adicionais' => false,
            'ingredientes_retirar_ui' => Produto::ING_RETIRAR_UI_STEPPER,
            'max_ingredientes_retirar' => 1,
        ]);

        ProdutoIngrediente::query()->create([
            'produto_id' => $produto->id,
            'nome' => 'Hortelã',
            'foto' => null,
            'ordem' => 0,
        ]);

        $payload = [
            'nome' => $produto->nome,
            'categoria_id' => null,
            'preco' => '9.90',
            'estoque' => 10,
            'descricao' => '',
            'visivel_loja' => '1',
            'ativo' => '1',
            'ingredientes_retirar_ui' => Produto::ING_RETIRAR_UI_CHECKBOX,
            'ingrediente_nomes' => ['Hortelã'],
            'ingrediente_foto_atual' => [''],
            'ingrediente_ids' => [(string) $produto->ingredientes()->first()->id],
            'max_ingredientes_retirar' => '1',
        ];

        $response = $this->actingAs($user)->put(
            route('empresa.produtos.update', $produto),
            $payload
        );

        $response->assertRedirect(route('empresa.produtos.index'));

        $produto->refresh();

        $this->assertSame(
            Produto::ING_RETIRAR_UI_CHECKBOX,
            $produto->ingredientes_retirar_ui,
            'O modo “checkbox” deve ser gravado quando ingredientes_retirar_ui vem no POST.'
        );
    }
}
