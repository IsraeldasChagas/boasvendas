<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\FidelidadeCartao;
use App\Models\FidelidadePrograma;
use App\Services\FidelidadeOtpEntrega;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FidelidadeOtpWhatsappTest extends TestCase
{
    use RefreshDatabase;

    public function test_solicitar_sem_cartao_retorna_aviso(): void
    {
        $empresa = $this->criarLojaComProgramaAtivo();

        $response = $this->post(route('publico.fidelidade.solicitar-codigo', ['slug' => $empresa->slug]), [
            'telefone' => '(11) 98888-7777',
        ]);

        $response->assertSessionHas('warning');
        $this->assertFalse(session()->has('fidelidade_otp_pending'));
    }

    public function test_fluxo_solicitar_verificar_com_cache_em_ambiente_testing(): void
    {
        $empresa = $this->criarLojaComProgramaAtivo();
        FidelidadeCartao::query()->create([
            'empresa_id' => $empresa->id,
            'telefone_normalizado' => '11988887777',
            'cpf_normalizado' => '52998224725',
            'email' => 'cli@exemplo.com',
            'selos' => 3,
            'total_resgates' => 0,
        ]);

        $this->post(route('publico.fidelidade.solicitar-codigo', ['slug' => $empresa->slug]), [
            'telefone' => '11 98888-7777',
        ])->assertRedirect(route('publico.fidelidade', ['slug' => $empresa->slug]))
            ->assertSessionHas('status');

        $this->assertTrue(session()->has('fidelidade_otp_pending'));

        $codigo = Cache::get('fidelidade_otp_codigo:'.$empresa->id.':11988887777');
        $this->assertIsString($codigo);
        $this->assertSame(6, strlen($codigo));

        $this->post(route('publico.fidelidade.verificar-codigo', ['slug' => $empresa->slug]), [
            'codigo' => '000000',
        ])->assertSessionHasErrors('codigo');

        $this->post(route('publico.fidelidade.verificar-codigo', ['slug' => $empresa->slug]), [
            'codigo' => $codigo,
        ])->assertRedirect(route('publico.fidelidade', ['slug' => $empresa->slug]))
            ->assertSessionHas('status');

        $this->assertTrue(session()->has('fidelidade_acesso'));
        $this->assertFalse(session()->has('fidelidade_otp_pending'));

        $this->get(route('publico.fidelidade', ['slug' => $empresa->slug]))
            ->assertOk()
            ->assertSee('selo', false);
    }

    public function test_verificar_codigo_aceita_digitos_com_espaco(): void
    {
        $empresa = $this->criarLojaComProgramaAtivo();
        FidelidadeCartao::query()->create([
            'empresa_id' => $empresa->id,
            'telefone_normalizado' => '11911112222',
            'cpf_normalizado' => '52998224725',
            'email' => 'esp@exemplo.com',
            'selos' => 1,
            'total_resgates' => 0,
        ]);

        $this->post(route('publico.fidelidade.solicitar-codigo', ['slug' => $empresa->slug]), [
            'telefone' => '11911112222',
        ]);

        $codigo = Cache::get('fidelidade_otp_codigo:'.$empresa->id.':11911112222');
        $this->assertIsString($codigo);

        $this->post(route('publico.fidelidade.verificar-codigo', ['slug' => $empresa->slug]), [
            'codigo' => substr($codigo, 0, 3).' '.substr($codigo, 3, 3),
        ])->assertRedirect(route('publico.fidelidade', ['slug' => $empresa->slug]))
            ->assertSessionHas('status');
    }

    public function test_com_webhook_configurado_chama_http(): void
    {
        config([
            'services.fidelidade_otp.notify_url' => 'https://example.test/notify',
            'services.fidelidade_otp.notify_bearer' => 'secret',
            'services.fidelidade_otp.notify_auth_type' => 'bearer',
            'services.fidelidade_otp.json_phone_key' => 'phone',
            'services.fidelidade_otp.json_message_key' => 'message',
        ]);

        Http::fake([
            'https://example.test/notify' => Http::response(['ok' => true], 200),
        ]);

        $empresa = $this->criarLojaComProgramaAtivo();
        FidelidadeCartao::query()->create([
            'empresa_id' => $empresa->id,
            'telefone_normalizado' => '11977776666',
            'cpf_normalizado' => '52998224725',
            'email' => 'cli2@exemplo.com',
            'selos' => 1,
            'total_resgates' => 0,
        ]);

        $this->post(route('publico.fidelidade.solicitar-codigo', ['slug' => $empresa->slug]), [
            'telefone' => '11977776666',
        ])->assertSessionHas('status');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.test/notify'
                && $request->hasHeader('Authorization', 'Bearer secret')
                && isset($request['phone'], $request['message'])
                && str_contains((string) $request['message'], 'código');
        });
    }

    public function test_solicitar_quando_webhook_falha_oferece_wa_me_na_sessao(): void
    {
        config([
            'services.fidelidade_otp.notify_url' => 'https://example.test/notify',
            'services.fidelidade_otp.notify_bearer' => 'secret',
            'services.fidelidade_otp.email_fallback' => false,
            'services.fidelidade_otp.wa_me_fallback' => true,
        ]);

        Http::fake([
            'https://example.test/notify' => Http::response('', 500),
        ]);

        $empresa = $this->criarLojaComProgramaAtivo();
        FidelidadeCartao::query()->create([
            'empresa_id' => $empresa->id,
            'telefone_normalizado' => '11933334444',
            'cpf_normalizado' => '52998224725',
            'email' => 'wa@exemplo.com',
            'selos' => 1,
            'total_resgates' => 0,
        ]);

        $this->post(route('publico.fidelidade.solicitar-codigo', ['slug' => $empresa->slug]), [
            'telefone' => '11933334444',
        ])->assertRedirect(route('publico.fidelidade', ['slug' => $empresa->slug]))
            ->assertSessionHas('status');

        $pending = session('fidelidade_otp_pending');
        $this->assertIsArray($pending);
        $this->assertSame(FidelidadeOtpEntrega::CANAL_WAME, $pending['canal']);
        $this->assertStringContainsString('https://wa.me/5511933334444', (string) ($pending['wa_me_url'] ?? ''));
    }

    private function criarLojaComProgramaAtivo(): Empresa
    {
        $empresa = Empresa::query()->create([
            'nome' => 'Loja OTP',
            'status' => 'ativa',
            'slug' => 'loja-otp-'.uniqid(),
        ]);
        FidelidadePrograma::query()->create([
            'empresa_id' => $empresa->id,
            'ativo' => true,
            'nome_exibicao' => 'Fidelidade',
            'pedidos_meta' => 10,
            'tipo_recompensa' => FidelidadePrograma::TIPO_DESCONTO_VALOR,
            'valor_desconto' => 5,
            'texto_recompensa' => null,
        ]);

        return $empresa;
    }
}
