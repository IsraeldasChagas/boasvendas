<?php

namespace Tests\Unit;

use App\Mail\FidelidadeOtpMail;
use App\Models\Empresa;
use App\Models\FidelidadeCartao;
use App\Models\FidelidadePrograma;
use App\Services\FidelidadeOtpEntrega;
use App\Services\FidelidadeOtpNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class FidelidadeOtpEntregaTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_automatico_usa_email_quando_whatsapp_falha(): void
    {
        Mail::fake();

        $this->mock(FidelidadeOtpNotifier::class, function ($m) {
            $m->shouldReceive('tentarEnviarCodigoWhatsapp')
                ->once()
                ->andReturn(['ok' => false, 'resultado' => FidelidadeOtpNotifier::RESULTADO_SEM_WEBHOOK]);
        });

        $empresa = $this->criarEmpresaECartaoComEmail();

        $entrega = $this->app->make(FidelidadeOtpEntrega::class);
        $r = $entrega->entregar($empresa, '11988887777', '654321', 10, FidelidadeOtpEntrega::PREF_AUTOMATICO);

        $this->assertTrue($r['ok']);
        $this->assertSame(FidelidadeOtpEntrega::CANAL_EMAIL, $r['canal']);
        Mail::assertSent(FidelidadeOtpMail::class, function (FidelidadeOtpMail $mail) {
            return str_contains($mail->corpoTexto, '654321');
        });
    }

    public function test_sem_destino_automatico_sem_email_no_cartao(): void
    {
        Mail::fake();

        $this->mock(FidelidadeOtpNotifier::class, function ($m) {
            $m->shouldReceive('tentarEnviarCodigoWhatsapp')
                ->once()
                ->andReturn(['ok' => false, 'resultado' => FidelidadeOtpNotifier::RESULTADO_SEM_WEBHOOK]);
        });

        $empresa = Empresa::query()->create([
            'nome' => 'Loja F',
            'status' => 'ativa',
            'slug' => 'loja-f-'.uniqid(),
        ]);
        FidelidadeCartao::query()->create([
            'empresa_id' => $empresa->id,
            'telefone_normalizado' => '11977776666',
            'cpf_normalizado' => '52998224725',
            'email' => '',
            'selos' => 0,
            'total_resgates' => 0,
        ]);

        $entrega = $this->app->make(FidelidadeOtpEntrega::class);
        $r = $entrega->entregar($empresa, '11977776666', '111111', 10, FidelidadeOtpEntrega::PREF_AUTOMATICO);

        $this->assertFalse($r['ok']);
        $this->assertSame(FidelidadeOtpEntrega::FALHA_SEM_DESTINO, $r['resultado']);
        Mail::assertNothingSent();
    }

    public function test_somente_whatsapp_nao_tenta_email(): void
    {
        Mail::fake();

        $this->mock(FidelidadeOtpNotifier::class, function ($m) {
            $m->shouldReceive('tentarEnviarCodigoWhatsapp')
                ->once()
                ->andReturn(['ok' => false, 'resultado' => FidelidadeOtpNotifier::RESULTADO_SEM_WEBHOOK]);
        });

        $empresa = $this->criarEmpresaECartaoComEmail();

        $entrega = $this->app->make(FidelidadeOtpEntrega::class);
        $r = $entrega->entregar($empresa, '11988887777', '222222', 10, FidelidadeOtpEntrega::PREF_WHATSAPP);

        $this->assertFalse($r['ok']);
        $this->assertSame(FidelidadeOtpEntrega::FALHA_WHATSAPP, $r['resultado']);
        Mail::assertNothingSent();
    }

    public function test_somente_email_nao_chama_whatsapp(): void
    {
        Mail::fake();

        $this->mock(FidelidadeOtpNotifier::class, function ($m) {
            $m->shouldReceive('tentarEnviarCodigoWhatsapp')->never();
        });

        $empresa = $this->criarEmpresaECartaoComEmail();

        $entrega = $this->app->make(FidelidadeOtpEntrega::class);
        $r = $entrega->entregar($empresa, '11988887777', '333333', 10, FidelidadeOtpEntrega::PREF_EMAIL);

        $this->assertTrue($r['ok']);
        $this->assertSame(FidelidadeOtpEntrega::CANAL_EMAIL, $r['canal']);
        Mail::assertSent(FidelidadeOtpMail::class);
    }

    public function test_somente_email_sem_email_retorna_falha(): void
    {
        Mail::fake();

        $this->mock(FidelidadeOtpNotifier::class, function ($m) {
            $m->shouldReceive('tentarEnviarCodigoWhatsapp')->never();
        });

        $empresa = Empresa::query()->create([
            'nome' => 'Loja G',
            'status' => 'ativa',
            'slug' => 'loja-g-'.uniqid(),
        ]);
        FidelidadeCartao::query()->create([
            'empresa_id' => $empresa->id,
            'telefone_normalizado' => '11966665555',
            'cpf_normalizado' => '52998224725',
            'email' => '',
            'selos' => 0,
            'total_resgates' => 0,
        ]);

        $entrega = $this->app->make(FidelidadeOtpEntrega::class);
        $r = $entrega->entregar($empresa, '11966665555', '444444', 10, FidelidadeOtpEntrega::PREF_EMAIL);

        $this->assertFalse($r['ok']);
        $this->assertSame(FidelidadeOtpEntrega::FALHA_SEM_EMAIL, $r['resultado']);
        Mail::assertNothingSent();
    }

    private function criarEmpresaECartaoComEmail(): Empresa
    {
        $empresa = Empresa::query()->create([
            'nome' => 'Loja E',
            'status' => 'ativa',
            'slug' => 'loja-e-'.uniqid(),
        ]);
        FidelidadePrograma::query()->create([
            'empresa_id' => $empresa->id,
            'ativo' => true,
            'nome_exibicao' => 'Fid',
            'pedidos_meta' => 10,
            'tipo_recompensa' => FidelidadePrograma::TIPO_DESCONTO_VALOR,
            'valor_desconto' => 1,
            'texto_recompensa' => null,
        ]);
        FidelidadeCartao::query()->create([
            'empresa_id' => $empresa->id,
            'telefone_normalizado' => '11988887777',
            'cpf_normalizado' => '52998224725',
            'email' => 'cliente@teste-otp.net',
            'selos' => 0,
            'total_resgates' => 0,
        ]);

        return $empresa;
    }
}
