<?php

namespace Tests\Unit;

use App\Models\Empresa;
use App\Models\FidelidadeCartao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FidelidadeCartaoConflitoCadastroTest extends TestCase
{
    use RefreshDatabase;

    private const CPF_A = '52998224725';

    private const CPF_B = '39053344705';

    public function test_sem_conflito_quando_nao_existe_cartao(): void
    {
        $empresa = Empresa::query()->create([
            'nome' => 'Loja Fid',
            'status' => 'ativa',
        ]);

        $this->assertNull(FidelidadeCartao::conflitoCadastroFidelidade(
            $empresa->id,
            '11999999999',
            self::CPF_A,
            'novo@exemplo.com',
            false
        ));
    }

    public function test_bloqueia_cpf_em_outro_telefone(): void
    {
        $empresa = Empresa::query()->create([
            'nome' => 'Loja Fid',
            'status' => 'ativa',
        ]);

        FidelidadeCartao::query()->create([
            'empresa_id' => $empresa->id,
            'telefone_normalizado' => '11888888888',
            'cpf_normalizado' => self::CPF_A,
            'email' => 'outro@exemplo.com',
            'selos' => 0,
            'total_resgates' => 0,
        ]);

        $c = FidelidadeCartao::conflitoCadastroFidelidade(
            $empresa->id,
            '11999999999',
            self::CPF_A,
            'novo@exemplo.com',
            false
        );
        $this->assertNotNull($c);
        $this->assertSame('cadastro_cpf', $c['field']);
    }

    public function test_bloqueia_email_em_outro_telefone(): void
    {
        $empresa = Empresa::query()->create([
            'nome' => 'Loja Fid',
            'status' => 'ativa',
        ]);

        FidelidadeCartao::query()->create([
            'empresa_id' => $empresa->id,
            'telefone_normalizado' => '11888888888',
            'cpf_normalizado' => self::CPF_A,
            'email' => 'compartilhado@exemplo.com',
            'selos' => 0,
            'total_resgates' => 0,
        ]);

        $c = FidelidadeCartao::conflitoCadastroFidelidade(
            $empresa->id,
            '11999999999',
            self::CPF_B,
            'compartilhado@exemplo.com',
            false
        );
        $this->assertNotNull($c);
        $this->assertSame('cadastro_email', $c['field']);
    }

    public function test_bloqueia_mesmo_telefone_com_outro_cpf(): void
    {
        $empresa = Empresa::query()->create([
            'nome' => 'Loja Fid',
            'status' => 'ativa',
        ]);

        FidelidadeCartao::query()->create([
            'empresa_id' => $empresa->id,
            'telefone_normalizado' => '11999999999',
            'cpf_normalizado' => self::CPF_A,
            'email' => 'a@exemplo.com',
            'selos' => 0,
            'total_resgates' => 0,
        ]);

        $c = FidelidadeCartao::conflitoCadastroFidelidade(
            $empresa->id,
            '11999999999',
            self::CPF_B,
            'b@exemplo.com',
            false
        );
        $this->assertNotNull($c);
        $this->assertSame('cadastro_telefone', $c['field']);
    }

    public function test_bloqueia_cadastro_identico_triplo(): void
    {
        $empresa = Empresa::query()->create([
            'nome' => 'Loja Fid',
            'status' => 'ativa',
        ]);

        FidelidadeCartao::query()->create([
            'empresa_id' => $empresa->id,
            'telefone_normalizado' => '11999999999',
            'cpf_normalizado' => self::CPF_A,
            'email' => 'mesmo@exemplo.com',
            'selos' => 0,
            'total_resgates' => 0,
        ]);

        $c = FidelidadeCartao::conflitoCadastroFidelidade(
            $empresa->id,
            '11999999999',
            self::CPF_A,
            'mesmo@exemplo.com',
            false
        );
        $this->assertNotNull($c);
        $this->assertSame('cadastro_telefone', $c['field']);
    }

    public function test_checkout_usa_chave_fidelidade_cpf_para_telefone_com_outro_cpf(): void
    {
        $empresa = Empresa::query()->create([
            'nome' => 'Loja Fid',
            'status' => 'ativa',
        ]);

        FidelidadeCartao::query()->create([
            'empresa_id' => $empresa->id,
            'telefone_normalizado' => '11999999999',
            'cpf_normalizado' => self::CPF_A,
            'email' => 'a@exemplo.com',
            'selos' => 0,
            'total_resgates' => 0,
        ]);

        $c = FidelidadeCartao::conflitoCadastroFidelidade(
            $empresa->id,
            '11999999999',
            self::CPF_B,
            'b@exemplo.com',
            true
        );
        $this->assertNotNull($c);
        $this->assertSame('fidelidade_cpf', $c['field']);
    }
}
