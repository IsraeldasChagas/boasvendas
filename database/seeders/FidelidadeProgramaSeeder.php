<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\FidelidadeCartao;
use App\Models\FidelidadePrograma;
use App\Models\Produto;
use Illuminate\Database\Seeder;

class FidelidadeProgramaSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::query()->where('slug', 'demo')->first();
        if (! $empresa) {
            return;
        }

        $produto = Produto::query()
            ->where('empresa_id', $empresa->id)
            ->where('sku', 'SKU-001')
            ->first();

        FidelidadePrograma::query()->updateOrCreate(
            ['empresa_id' => $empresa->id],
            [
                'ativo' => true,
                'nome_exibicao' => 'Cartão do lanche',
                'pedidos_meta' => 10,
                'tipo_recompensa' => FidelidadePrograma::TIPO_PRODUTO,
                'produto_id' => $produto?->id,
                'valor_desconto' => null,
                'texto_recompensa' => 'Mostre na loja ou informe no próximo pedido para retirar seu Combo Rua.',
            ]
        );

        $ana = Cliente::query()
            ->where('empresa_id', $empresa->id)
            ->where('nome', 'Ana Costa')
            ->first();

        if ($ana && $ana->telefone) {
            $norm = FidelidadeCartao::normalizarTelefone($ana->telefone);
            if (strlen($norm) >= 8) {
                FidelidadeCartao::query()->firstOrCreate(
                    [
                        'empresa_id' => $empresa->id,
                        'telefone_normalizado' => $norm,
                    ],
                    [
                        'cliente_id' => $ana->id,
                        'selos' => 3,
                    ]
                );
            }
        }
    }
}
