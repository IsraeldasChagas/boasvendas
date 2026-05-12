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

    public function test_usa_email_quando_whatsapp_falha_e_fallback_ligado(): void
    {
        Mail::fake();
        config(['services.fidelidade_otp.email_fallback' => true]);

        $this->mock(FidelidadeOtpNotifier::class, function ($m) {
            $m->shouldReceive('tentarEnviarCodigoWhatsapp')
                ->once()
                ->andReturn(['ok' => false, 'resultado' => FidelidadeOtpNotifier::RESULTADO_SEM_WEBHOOK]);
        });

        $empresa = $this->criarEmpresaECartaoComEmail();

        $entrega = $this->app->make(FidelidadeOtpEntrega::class);
        $r = $entrega->entregar($empresa, '11988887777', '654321', 10);

        $this->assertTrue($r['ok']);
        $this->assertSame(FidelidadeOtpEntrega::CANAL_EMAIL, $r['canal']);
        Mail::assertSent(FidelidadeOtpMail::class, function (FidelidadeOtpMail $mail) {
            return str_contains($mail->corpoTexto, '654321');
        });
    }

    public function test_sem_destino_quando_whatsapp_falha_fallback_ligado_e_sem_email_no_cartao(): void
    {
        Mail::fake();
        config([
            'services.fidelidade_otp.email_fallback' => true,
            'services.fidelidade_otp.wa_me_fallback' => false,
        ]);

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
        $r = $entrega->entregar($empresa, '11977776666', '111111', 10);

        $this->assertFalse($r['ok']);
        $this->assertSame(FidelidadeOtpEntrega::FALHA_SEM_DESTINO, $r['resultado']);
        Mail::assertNothingSent();
    }

    public function test_usa_wa_me_quando_email_nao_disponivel_e_fallback_ligado(): void
    {
        Mail::fake();
        config([
            'services.fidelidade_otp.email_fallback' => true,
            'services.fidelidade_otp.wa_me_fallback' => true,
        ]);

        $this->mock(FidelidadeOtpNotifier::class, function ($m) {
            $m->shouldReceive('tentarEnviarCodigoWhatsapp')
                ->once()
                ->andReturn(['ok' => false, 'resultado' => FidelidadeOtpNotifier::RESULTADO_SEM_WEBHOOK]);
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
        $r = $entrega->entregar($empresa, '11966665555', '222333', 10);

        $this->assertTrue($r['ok']);
        $this->assertSame(FidelidadeOtpEntrega::CANAL_WAME, $r['canal']);
        $this->assertStringContainsString('222333', (string) ($r['wa_me_url'] ?? ''));
        Mail::assertNothingSent();
    }

    public function test_so_whatsapp_quando_fallback_desligado_usa_wa_me_sem_email(): void
    {
        Mail::fake();
        config([
            'services.fidelidade_otp.email_fallback' => false,
            'services.fidelidade_otp.wa_me_fallback' => true,
        ]);

        $this->mock(FidelidadeOtpNotifier::class, function ($m) {
            $m->shouldReceive('tentarEnviarCodigoWhatsapp')
                ->once()
                ->andReturn(['ok' => false, 'resultado' => FidelidadeOtpNotifier::RESULTADO_HTTP, 'http_status' => 401]);
        });

        $empresa = $this->criarEmpresaECartaoComEmail();

        $entrega = $this->app->make(FidelidadeOtpEntrega::class);
        $r = $entrega->entregar($empresa, '11988887777', '999888', 10);

        $this->assertTrue($r['ok']);
        $this->assertSame(FidelidadeOtpEntrega::CANAL_WAME, $r['canal']);
        $this->assertStringContainsString('https://wa.me/5511988887777', (string) ($r['wa_me_url'] ?? ''));
        $this->assertStringContainsString('999888', (string) ($r['wa_me_url'] ?? ''));
        Mail::assertNothingSent();
    }

    public function test_falha_whatsapp_quando_wa_me_desativado_e_sem_email_fallback(): void
    {
        Mail::fake();
        config([
            'services.fidelidade_otp.email_fallback' => false,
            'services.fidelidade_otp.wa_me_fallback' => false,
        ]);

        $this->mock(FidelidadeOtpNotifier::class, function ($m) {
            $m->shouldReceive('tentarEnviarCodigoWhatsapp')
                ->once()
                ->andReturn(['ok' => false, 'resultado' => FidelidadeOtpNotifier::RESULTADO_SEM_WEBHOOK]);
        });

        $empresa = $this->criarEmpresaECartaoComEmail();

        $entrega = $this->app->make(FidelidadeOtpEntrega::class);
        $r = $entrega->entregar($empresa, '11988887777', '111222', 10);

        $this->assertFalse($r['ok']);
        $this->assertSame(FidelidadeOtpEntrega::FALHA_WHATSAPP, $r['resultado']);
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
