<?php

namespace Tests\Unit;

use App\Enums\Estoque\EstoqueMovimentoTipo;
use App\Enums\Estoque\UnidadeMedida;
use App\Models\Empresa;
use App\Models\Insumo;
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
        $this->assertSame(-3.0, (float) $mov->delta);
        $this->assertSame(7.0, (float) $mov->saldo_apos);
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
        $this->assertSame(-2.0, (float) $mov->delta);
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
        $this->assertSame(2.0, (float) $mov->delta);
    }

    public function test_repor_e_ajustar_registram_movimentos(): void
    {
        $p = $this->produto(0);

        $this->service->repor($p, 12, 'compra da feira', null);
        $this->assertSame(12, $p->fresh()->estoque);

        $mov = $this->service->ajustar($p, 9, 'contagem', null);
        $this->assertSame(9, $p->fresh()->estoque);
        $this->assertSame(-3.0, (float) $mov->delta);
        $this->assertSame(EstoqueMovimentoTipo::Ajuste, $mov->tipo);
    }

    public function test_ajustar_para_mesmo_saldo_nao_gera_movimento(): void
    {
        $p = $this->produto(5);

        $this->assertNull($this->service->ajustar($p, 5));
    }

    public function test_repor_insumo_converte_kg_para_gramas(): void
    {
        $insumo = $this->insumo('Polpa de açaí', UnidadeMedida::Grama);

        $mov = $this->service->reporInsumo($insumo, 2.5, UnidadeMedida::Quilo, 'compra');

        $this->assertSame(2500.0, (float) $insumo->fresh()->saldo);
        $this->assertSame(2500.0, (float) $mov->delta);
        $this->assertSame(UnidadeMedida::Grama, $mov->unidade);
    }

    public function test_unidade_incompativel_com_insumo_e_recusada(): void
    {
        $insumo = $this->insumo('Leite condensado', UnidadeMedida::Mililitro);

        $this->expectException(ValidationException::class);
        $this->service->reporInsumo($insumo, 1, UnidadeMedida::Quilo);
    }

    public function test_ajustar_insumo_define_saldo_absoluto(): void
    {
        $insumo = $this->insumo('Granola', UnidadeMedida::Grama, 900);

        $mov = $this->service->ajustarInsumo($insumo, 0.5, UnidadeMedida::Quilo, 'contagem');

        $this->assertSame(500.0, (float) $insumo->fresh()->saldo);
        $this->assertSame(-400.0, (float) $mov->delta);
        $this->assertSame(EstoqueMovimentoTipo::Ajuste, $mov->tipo);
    }

    public function test_receita_baixa_insumos_na_venda(): void
    {
        $prato = $this->produto(10, nome: 'Açaí 500ml');
        $polpa = $this->insumo('Polpa de açaí', UnidadeMedida::Grama, 2000);
        $copo = $this->insumo('Copo 500ml', UnidadeMedida::Unidade, 50);

        $this->receita($prato, $polpa, 0.3, UnidadeMedida::Quilo);
        $this->receita($prato, $copo, 1, UnidadeMedida::Unidade);

        $this->service->baixar($prato, 3, EstoqueMovimentoTipo::VendaLoja, comFicha: true);

        $this->assertSame(7, $prato->fresh()->estoque);
        $this->assertSame(1100.0, (float) $polpa->fresh()->saldo);
        $this->assertSame(47.0, (float) $copo->fresh()->saldo);

        $mov = $polpa->movimentos()->first();
        $this->assertSame(EstoqueMovimentoTipo::ConsumoFicha, $mov->tipo);
        $this->assertSame(-900.0, (float) $mov->delta);
    }

    public function test_rendimento_divide_o_consumo_por_porcao(): void
    {
        $prato = $this->produto(10, nome: 'Bolo de pote');
        $prato->update(['ficha_rendimento' => 4]);
        $massa = $this->insumo('Massa de bolo', UnidadeMedida::Grama, 2000);

        // A receita usa 1 kg e rende 4 porções: cada venda consome 250 g.
        $this->receita($prato, $massa, 1, UnidadeMedida::Quilo);

        $this->service->baixar($prato, 2, EstoqueMovimentoTipo::VendaPdv, comFicha: true);

        $this->assertSame(1500.0, (float) $massa->fresh()->saldo);
    }

    public function test_receita_nao_trava_venda_com_insumo_insuficiente(): void
    {
        $prato = $this->produto(10, nome: 'Açaí 500ml');
        $polpa = $this->insumo('Polpa de açaí', UnidadeMedida::Grama, 100);

        $this->receita($prato, $polpa, 300, UnidadeMedida::Grama);

        $this->service->baixar($prato, 2, EstoqueMovimentoTipo::VendaLoja, comFicha: true);

        $this->assertSame(8, $prato->fresh()->estoque);
        $this->assertSame(0.0, (float) $polpa->fresh()->saldo);
        $this->assertStringContainsString('consumo parcial', (string) $polpa->movimentos()->first()->observacao);
    }

    public function test_devolucao_com_receita_devolve_insumos(): void
    {
        $prato = $this->produto(10, nome: 'Açaí 500ml');
        $polpa = $this->insumo('Polpa de açaí', UnidadeMedida::Grama, 1000);

        $this->receita($prato, $polpa, 200, UnidadeMedida::Grama);

        $this->service->baixar($prato, 2, EstoqueMovimentoTipo::VendaLoja, comFicha: true);
        $this->service->devolver($prato, 2, EstoqueMovimentoTipo::Cancelamento, comFicha: true);

        $this->assertSame(10, $prato->fresh()->estoque);
        $this->assertSame(1000.0, (float) $polpa->fresh()->saldo);
    }

    public function test_porcoes_possiveis_usa_o_insumo_mais_escasso(): void
    {
        $prato = $this->produto(10, nome: 'Açaí 500ml');
        $polpa = $this->insumo('Polpa de açaí', UnidadeMedida::Grama, 1000);
        $copo = $this->insumo('Copo 500ml', UnidadeMedida::Unidade, 2);

        $this->receita($prato, $polpa, 200, UnidadeMedida::Grama); // dá 5
        $this->receita($prato, $copo, 1, UnidadeMedida::Unidade);  // dá 2

        $this->assertSame(2, $prato->porcoesPossiveisPelaFicha());
        $this->assertSame('Copo 500ml', $prato->insumoLimitanteDaFicha()->insumo->nome);
    }

    public function test_produto_sem_ficha_nao_calcula_porcoes(): void
    {
        $this->assertNull($this->produto(5)->porcoesPossiveisPelaFicha());
    }

    private function insumo(string $nome, UnidadeMedida $base, float $saldo = 0): Insumo
    {
        return Insumo::query()->create([
            'empresa_id' => $this->empresa->id,
            'nome' => $nome,
            'unidade_base' => $base,
            'saldo' => $saldo,
        ]);
    }

    private function receita(Produto $produto, Insumo $insumo, float $quantidade, UnidadeMedida $unidade): ProdutoFichaItem
    {
        return ProdutoFichaItem::query()->create([
            'empresa_id' => $this->empresa->id,
            'produto_id' => $produto->id,
            'insumo_id' => $insumo->id,
            'quantidade' => $quantidade,
            'unidade' => $unidade,
            'quantidade_base' => $unidade->paraBase($quantidade),
        ]);
    }
}
