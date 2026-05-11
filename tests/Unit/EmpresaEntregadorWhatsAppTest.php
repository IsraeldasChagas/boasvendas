<?php

namespace Tests\Unit;

use App\Models\Empresa;
use App\Models\EmpresaEntregador;
use App\Models\Pedido;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmpresaEntregadorWhatsAppTest extends TestCase
{
    #[Test]
    public function monta_link_whatsapp_com_pedido_e_slug(): void
    {
        $empresa = new Empresa([
            'nome' => 'Loja Teste',
            'slug' => 'loja-teste',
        ]);

        $pedido = new Pedido([
            'codigo_publico' => 'VF-001',
            'cliente_nome' => 'Maria',
            'cliente_telefone' => '(11) 98888-7777',
            'endereco' => 'Rua das Flores, 10',
            'complemento' => 'Apto 2',
        ]);
        $pedido->entregador_token = 'abc123token';

        $ent = new EmpresaEntregador([
            'whatsapp' => '11977776666',
        ]);

        $url = $ent->urlWhatsAppPedido($pedido, $empresa);
        $this->assertNotNull($url);
        $this->assertStringStartsWith('https://wa.me/5511977776666', $url);
        $this->assertStringContainsString('VF-001', urldecode($url));
    }

    #[Test]
    public function rotulo_moto_agrupa_campos(): void
    {
        $ent = new EmpresaEntregador([
            'moto_modelo' => 'CG 160',
            'moto_cor' => 'Preta',
            'moto_placa' => 'abc1d23',
        ]);
        $this->assertSame('CG 160 · Preta · ABC1D23', $ent->rotuloMotoCurto());
    }
}
