<?php

namespace Tests\Unit;

use App\Models\Empresa;
use App\Models\Produto;
use App\Models\ProdutoIngrediente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdutoLimiteRetiradaIngredientesTest extends TestCase
{
    use RefreshDatabase;

    public function test_null_max_usa_quantidade_de_ingredientes_como_limite(): void
    {
        $p = Produto::query()->create([
            'empresa_id' => Empresa::query()->create([
                'nome' => 'E',
                'status' => 'ativa',
            ])->id,
            'sku' => 'SKU-LIM-1',
            'nome' => 'Teste',
            'preco' => 1,
            'estoque' => 1,
            'max_ingredientes_retirar' => null,
        ]);

        ProdutoIngrediente::query()->create([
            'produto_id' => $p->id,
            'nome' => 'A',
            'ordem' => 0,
        ]);
        ProdutoIngrediente::query()->create([
            'produto_id' => $p->id,
            'nome' => 'B',
            'ordem' => 1,
        ]);

        $p->load('ingredientes');

        $this->assertSame(2, $p->limiteRetiradaIngredientesNaLoja());
    }

    public function test_zero_no_banco_com_ingredientes_usa_padrao_como_null(): void
    {
        $p = Produto::query()->create([
            'empresa_id' => Empresa::query()->create([
                'nome' => 'E2',
                'status' => 'ativa',
            ])->id,
            'sku' => 'SKU-LIM-2',
            'nome' => 'Teste',
            'preco' => 1,
            'estoque' => 1,
            'max_ingredientes_retirar' => 0,
        ]);

        ProdutoIngrediente::query()->create([
            'produto_id' => $p->id,
            'nome' => 'A',
            'ordem' => 0,
        ]);

        $p->load('ingredientes');

        $this->assertSame(1, $p->limiteRetiradaIngredientesNaLoja());
    }

    public function test_modo_retirar_normaliza_checkbox(): void
    {
        $p = Produto::query()->make([
            'ingredientes_retirar_ui' => ' Checkbox ',
        ]);

        $this->assertSame(Produto::ING_RETIRAR_UI_CHECKBOX, $p->modoRetirarIngredientesNaLoja());
    }

    public function test_modo_acrescimos_na_loja_normaliza_checkbox(): void
    {
        $p = Produto::query()->make([
            'acrescimos_loja_ui' => ' Checkbox ',
        ]);

        $this->assertSame(Produto::ACRESCIMO_LOJA_UI_CHECKBOX, $p->modoAcrescimosNaLoja());
    }
}
