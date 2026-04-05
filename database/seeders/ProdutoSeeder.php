<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\Produto;
use Illuminate\Database\Seeder;

class ProdutoSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::query()->where('nome', 'Lanchonete Demo')->first();
        if (! $empresa) {
            return;
        }

        $catId = fn (string $nome) => Categoria::query()
            ->where('empresa_id', $empresa->id)
            ->where('nome', $nome)
            ->value('id');

        $rows = [
            [
                'sku' => 'SKU-001',
                'nome' => 'Combo Rua',
                'categoria_id' => $catId('Lanches'),
                'preco' => 28.90,
                'estoque' => 42,
                'descricao' => null,
                'visivel_loja' => true,
                'ativo' => true,
            ],
            [
                'sku' => 'SKU-014',
                'nome' => 'Brownie',
                'categoria_id' => $catId('Sobremesa'),
                'preco' => 9.90,
                'estoque' => 8,
                'descricao' => null,
                'visivel_loja' => true,
                'ativo' => true,
            ],
            [
                'sku' => 'SKU-022',
                'nome' => 'Água 500ml',
                'categoria_id' => $catId('Bebidas'),
                'preco' => 3.50,
                'estoque' => 0,
                'descricao' => null,
                'visivel_loja' => true,
                'ativo' => false,
            ],
        ];

        foreach ($rows as $row) {
            Produto::query()->firstOrCreate(
                ['empresa_id' => $empresa->id, 'sku' => $row['sku']],
                $row + ['empresa_id' => $empresa->id]
            );
        }
    }
}
