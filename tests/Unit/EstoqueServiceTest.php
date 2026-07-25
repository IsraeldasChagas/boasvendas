<?php

namespace Tests\Unit;

use App\Enums\Estoque\EstoqueMovimentoTipo;
use App\Models\Empresa;
use App\Models\Produto;
use App\Models\ProdutoFichaItem;
use App\Services\Estoque\EstoqueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EstoqueServiceTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private EstoqueService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->empresa = Empresa::query()->create(['nome' => 'Loja Estoque', 'status' => 'ativa']);
        $this->service = app(EstoqueService::class);
    }

    private function produto(int $estoque = 10, bool $controla = true, string $nome = 'X-Burger'): Produto
    {
        return Produto::query()->create([
            'empresa_id' => $this->empresa->id,
            'sku' => 'SKU-'.uniqid(),
            'nome' => $nome,
            'preco' => 10.00,
            'estoque' => $estoque,
            'controla_estoque' => $controla,
        ]);
    }

    public function test_baixa_grava_movimento_com_saldo_apos(): void
    {
        $p = $this->produto(10);

        $mov = $this->service->baixar($p, 3, EstoqueMovimentoTipo::VendaPdv);

        $this->assertSame(7, $p->fresh()->estoque);
        $this->assertSame(-3, $mov->delta);
        $this->assertSame(7, $mov->saldo_apos);
        $this->assertSame(EstoqueMovimentoTipo::VendaPdv, $mov->tipo);
    }

    public function test_baixa_bloqueia_saldo_insuficiente(): void
    {
        $p = $this->produto(2);

        $this->expectException(ValidationException::class);
        $this->service->baixar($p, 5, EstoqueMovimentoTipo::VendaLoja);
    }

    public function test_baixa_parcial_quando_nao_bloqueante(): void
    {
        $p = $this->produto(2);

        $mov = $this->service->baixar($p, 5, EstoqueMovimentoTipo::VendaMesa, bloquearSeInsuficiente: false);

        $this->assertSame(0, $p->fresh()->estoque);
        $this->assertSame(-2, $mov->delta);
        $this->assertStringContainsString('baixa parcial', (string) $mov->observacao);
    }

    public function test_produto_sem_controle_nao_baixa_nem_grava(): void
    {
        $p = $this->produto(5, controla: false);

        $mov = $this->service->baixar($p, 3, EstoqueMovimentoTipo::VendaPdv);

        $this->assertNull($mov);
        $this->assertSame(5, $p->fresh()->estoque);
    }

    public function test_devolver_aumenta_saldo(): void
    {
        $p = $this->produto(4);

        $mov = $this->service->devolver($p, 2, EstoqueMovimentoTipo::Cancelamento);

        $this->assertSame(6, $p->fresh()->estoque);
        $this->assertSame(2, $mov->delta);
    }

    public function test_repor_e_ajustar_registram_movimentos(): void
    {
        $p = $this->produto(0);

        $this->service->repor($p, 12, 'compra da feira', null);
        $this->assertSame(12, $p->fresh()->estoque);

        $mov = $this->service->ajustar($p, 9, 'contagem', null);
        $this->assertSame(9, $p->fresh()->estoque);
        $this->assertSame(-3, $mov->delta);
        $this->assertSame(EstoqueMovimentoTipo::Ajuste, $mov->tipo);
    }

    public function test_ajustar_para_mesmo_saldo_nao_gera_movimento(): void
    {
        $p = $this->produto(5);

        $this->assertNull($this->service->ajustar($p, 5));
    }

    public function test_ficha_tecnica_baixa_insumos_na_venda(): void
    {
        $final = $this->produto(10, nome: 'Açaí 500ml');
        $insumo = $this->produto(20, nome: 'Copo 500ml');

        ProdutoFichaItem::query()->create([
            'empresa_id' => $this->empresa->id,
            'produto_id' => $final->id,
            'insumo_produto_id' => $insumo->id,
            'quantidade' => 2,
        ]);

        $this->service->baixar($final, 3, EstoqueMovimentoTipo::VendaLoja, comFicha: true);

        $this->assertSame(7, $final->fresh()->estoque);
        $this->assertSame(14, $insumo->fresh()->estoque);

        $movInsumo = $insumo->estoqueMovimentos()->first();
        $this->assertSame(EstoqueMovimentoTipo::ConsumoFicha, $movInsumo->tipo);
        $this->assertSame(-6, $movInsumo->delta);
    }

    public function test_ficha_tecnica_nao_trava_venda_com_insumo_zerado(): void
    {
        $final = $this->produto(10, nome: 'Açaí 500ml');
        $insumo = $this->produto(1, nome: 'Copo 500ml');

        ProdutoFichaItem::query()->create([
            'empresa_id' => $this->empresa->id,
            'produto_id' => $final->id,
            'insumo_produto_id' => $insumo->id,
            'quantidade' => 2,
        ]);

        $this->service->baixar($final, 3, EstoqueMovimentoTipo::VendaLoja, comFicha: true);

        $this->assertSame(7, $final->fresh()->estoque);
        $this->assertSame(0, $insumo->fresh()->estoque);
        $this->assertStringContainsString('baixa parcial', (string) $insumo->estoqueMovimentos()->first()->observacao);
    }

    public function test_devolucao_com_ficha_devolve_insumos(): void
    {
        $final = $this->produto(10, nome: 'Açaí 500ml');
        $insumo = $this->produto(20, nome: 'Copo 500ml');

        ProdutoFichaItem::query()->create([
            'empresa_id' => $this->empresa->id,
            'produto_id' => $final->id,
            'insumo_produto_id' => $insumo->id,
            'quantidade' => 1,
        ]);

        $this->service->baixar($final, 2, EstoqueMovimentoTipo::VendaLoja, comFicha: true);
        $this->service->devolver($final, 2, EstoqueMovimentoTipo::Cancelamento, comFicha: true);

        $this->assertSame(10, $final->fresh()->estoque);
        $this->assertSame(20, $insumo->fresh()->estoque);
    }
}
