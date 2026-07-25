<?php

namespace Tests\Unit;

use App\Enums\Fiscal\FiscalAmbiente;
use App\Enums\Fiscal\FiscalRegimeTributario;
use App\Enums\Fiscal\FiscalTipoPessoa;
use App\Models\Empresa;
use App\Models\FiscalEmitente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FiscalEmitenteTest extends TestCase
{
    use RefreshDatabase;

    public function test_cadastra_pessoa_fisica_com_endereco_fiscal_completo(): void
    {
        $empresa = Empresa::query()->create(['nome' => 'Loja PF', 'status' => 'ativa']);

        $emitente = FiscalEmitente::query()->create([
            'empresa_id' => $empresa->id,
            'tipo_pessoa' => FiscalTipoPessoa::PessoaFisica,
            'razao_social' => 'Maria da Silva',
            'cpf' => '52998224725',
            'indicador_ie' => 'nao_contribuinte',
            'regime_tributario' => FiscalRegimeTributario::RegimeNormal,
            'cep' => '76801000',
            'logradouro' => 'Rua Exemplo',
            'numero' => '10',
            'bairro' => 'Centro',
            'municipio' => 'Porto Velho',
            'codigo_municipio_ibge' => '1100205',
            'uf' => 'RO',
            'ambiente' => FiscalAmbiente::Homologacao,
        ]);

        $this->assertSame(FiscalTipoPessoa::PessoaFisica, $emitente->tipo_pessoa);
        $this->assertSame('529.***.***-25', $emitente->documentoMascarado());
        $this->assertTrue($emitente->cadastroFiscalCompleto());
    }

    public function test_cadastro_incompleto_nao_fica_pronto_para_emissao(): void
    {
        $emitente = new FiscalEmitente([
            'tipo_pessoa' => FiscalTipoPessoa::PessoaJuridica,
            'razao_social' => 'Empresa sem endereço',
            'cnpj' => '11222333000181',
            'regime_tributario' => FiscalRegimeTributario::SimplesNacional,
        ]);

        $this->assertFalse($emitente->cadastroFiscalCompleto());
    }
}
