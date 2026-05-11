<?php

namespace Tests\Unit;

use App\Models\FidelidadeCartao;
use Tests\TestCase;

class FidelidadeCartaoCpfTest extends TestCase
{
    public function test_normalizar_cpf(): void
    {
        $this->assertSame('52998224725', FidelidadeCartao::normalizarCpf('529.982.247-25'));
        $this->assertNull(FidelidadeCartao::normalizarCpf('123'));
        $this->assertNull(FidelidadeCartao::normalizarCpf(''));
    }

    public function test_cpf_valido(): void
    {
        $this->assertTrue(FidelidadeCartao::cpfValido('52998224725'));
        $this->assertFalse(FidelidadeCartao::cpfValido('11111111111'));
        $this->assertFalse(FidelidadeCartao::cpfValido('52998224726'));
    }

    public function test_email_mascarado(): void
    {
        $c = new FidelidadeCartao(['email' => 'maria.silva@exemplo.com.br']);
        $this->assertStringContainsString('@exemplo.com.br', $c->emailMascarado());
        $this->assertStringContainsString('**', $c->emailMascarado());
    }
}
