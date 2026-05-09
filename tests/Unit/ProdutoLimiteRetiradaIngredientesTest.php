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

    public function test_zero_explicito_permite_zero(): void
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

        $this->assertSame(0, $p->limiteRetiradaIngredientesNaLoja());
    }
}
