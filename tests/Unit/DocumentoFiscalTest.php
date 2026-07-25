<?php

namespace Tests\Unit;

use App\Support\Fiscal\DocumentoFiscal;
use PHPUnit\Framework\TestCase;

class DocumentoFiscalTest extends TestCase
{
    public function test_valida_cpf_com_ou_sem_mascara(): void
    {
        $this->assertTrue(DocumentoFiscal::cpfValido('529.982.247-25'));
        $this->assertTrue(DocumentoFiscal::cpfValido('52998224725'));
        $this->assertFalse(DocumentoFiscal::cpfValido('52998224724'));
        $this->assertFalse(DocumentoFiscal::cpfValido('11111111111'));
    }

    public function test_valida_cnpj_com_ou_sem_mascara(): void
    {
        $this->assertTrue(DocumentoFiscal::cnpjValido('11.222.333/0001-81'));
        $this->assertTrue(DocumentoFiscal::cnpjValido('11222333000181'));
        $this->assertFalse(DocumentoFiscal::cnpjValido('11222333000180'));
        $this->assertFalse(DocumentoFiscal::cnpjValido('00000000000000'));
    }

    public function test_mascara_documento_sem_expor_todos_os_digitos(): void
    {
        $this->assertSame('529.***.***-25', DocumentoFiscal::mascarar('52998224725'));
        $this->assertSame('11.***.***/****-81', DocumentoFiscal::mascarar('11222333000181'));
    }
}
