<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Garante /loja/demo em bases só com schema (migrate / SQL import), sem depender de db:seed.
 * Idempotente: pode correr várias vezes.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $empresaId = DB::table('empresas')->where('slug', 'demo')->value('id');

        if (! $empresaId) {
            $empresaId = DB::table('empresas')->insertGetId([
                'nome' => 'Lanchonete Demo',
                'slug' => 'demo',
                'email_contato' => 'lanchonete-demo@vendaffacil.com.br',
                'cnpj' => null,
                'plano_id' => null,
                'status' => 'ativa',
                'modulos_resumo' => 'VE + Delivery',
                'cliente_desde' => '2025-01-12',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $catId = function (string $nome, int $ordem) use ($empresaId, $now): int {
            $row = DB::table('categorias')
                ->where('empresa_id', $empresaId)
                ->where('nome', $nome)
                ->first();

            if ($row) {
                return (int) $row->id;
            }

            return (int) DB::table('categorias')->insertGetId([
                'empresa_id' => $empresaId,
                'nome' => $nome,
                'ordem' => $ordem,
                'ativo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        };

        $idLanches = $catId('Lanches', 20);
        $idBebidas = $catId('Bebidas', 30);
        $idSobremesa = $catId('Sobremesa', 41);

        $produtos = [
            [
                'sku' => 'SKU-001',
                'nome' => 'Combo Rua',
                'categoria_id' => $idLanches,
                'preco' => 28.90,
                'estoque' => 42,
                'descricao' => null,
                'visivel_loja' => true,
                'ativo' => true,
            ],
            [
                'sku' => 'SKU-014',
                'nome' => 'Brownie',
                'categoria_id' => $idSobremesa,
                'preco' => 9.90,
                'estoque' => 8,
                'descricao' => null,
                'visivel_loja' => true,
                'ativo' => true,
            ],
            [
                'sku' => 'SKU-022',
                'nome' => 'Água 500ml',
                'categoria_id' => $idBebidas,
                'preco' => 3.50,
                'estoque' => 0,
                'descricao' => null,
                'visivel_loja' => true,
                'ativo' => false,
            ],
        ];

        foreach ($produtos as $p) {
            $exists = DB::table('produtos')
                ->where('empresa_id', $empresaId)
                ->where('sku', $p['sku'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('produtos')->insert(array_merge($p, [
                'empresa_id' => $empresaId,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        // Não remove dados: evita apagar personalizações em produção.
    }
};
