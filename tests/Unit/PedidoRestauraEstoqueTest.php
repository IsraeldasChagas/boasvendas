<?php

namespace Tests\Unit;

use App\Enums\Estoque\EstoqueMovimentoTipo;
use App\Models\Empresa;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedidoRestauraEstoqueTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurar_estoque_devolve_itens_e_grava_movimento(): void
    {
        $empresa = Empresa::query()->create(['nome' => 'Loja Pedido', 'status' => 'ativa']);
        $produto = Produto::query()->create([
            'empresa_id' => $empresa->id,
            'sku' => 'SKU-REFRI-1',
            'nome' => 'Refri lata',
            'preco' => 6.00,
            'estoque' => 8,
            'controla_estoque' => true,
        ]);

        $pedido = Pedido::query()->create([
            'empresa_id' => $empresa->id,
            'codigo_publico' => 'BV-TESTE1',
            'canal' => Pedido::CANAL_LOJA,
            'cliente_nome' => 'Cliente',
            'cliente_telefone' => '11999999999',
            'endereco' => 'Rua X',
            'forma_pagamento' => 'dinheiro',
            'status' => 'recebido',
            'subtotal' => 12.00,
            'taxa_entrega' => 0,
            'total' => 12.00,
        ]);

        PedidoItem::query()->create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produto->id,
            'nome_produto' => $produto->nome,
            'preco_unitario' => 6.00,
            'quantidade' => 2,
            'subtotal' => 12.00,
        ]);

        $pedido->restaurarEstoqueProdutos();

        $this->assertSame(10, $produto->fresh()->estoque);

        $mov = $produto->estoqueMovimentos()->first();
        $this->assertSame(EstoqueMovimentoTipo::Cancelamento, $mov->tipo);
        $this->assertSame(2, $mov->delta);
        $this->assertSame(10, $mov->saldo_apos);
    }
}
