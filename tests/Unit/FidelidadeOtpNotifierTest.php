<?php

namespace Tests\Unit;

use App\Services\FidelidadeOtpNotifier;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FidelidadeOtpNotifierTest extends TestCase
{
    public function test_evolution_envia_number_text_e_header_apikey(): void
    {
        config([
            'services.fidelidade_otp.notify_url' => 'https://evo.test/message/sendText/inst1',
            'services.fidelidade_otp.notify_bearer' => 'chave-api',
            'services.fidelidade_otp.notify_auth_type' => 'apikey',
            'services.fidelidade_otp.json_phone_key' => 'number',
            'services.fidelidade_otp.json_message_key' => 'text',
            'services.fidelidade_otp.json_extra' => '{"linkPreview":false}',
        ]);

        Http::fake([
            'https://evo.test/message/sendText/inst1' => Http::response(['key' => ['id' => 'x']], 201),
        ]);

        $n = $this->app->make(FidelidadeOtpNotifier::class);
        $r = $n->tentarEnviarCodigoWhatsapp('11988887777', 'Olá código 123456');

        $this->assertTrue($r['ok']);
        $this->assertSame(FidelidadeOtpNotifier::RESULTADO_OK, $r['resultado']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://evo.test/message/sendText/inst1'
                && $request->hasHeader('apikey', 'chave-api')
                && $request['number'] === '5511988887777'
                && $request['text'] === 'Olá código 123456'
                && $request['linkPreview'] === false;
        });
    }

    public function test_http_200_com_error_true_falha(): void
    {
        config([
            'services.fidelidade_otp.notify_url' => 'https://evo.test/send',
            'services.fidelidade_otp.notify_bearer' => 'k',
            'services.fidelidade_otp.notify_auth_type' => 'apikey',
        ]);

        Http::fake([
            'https://evo.test/send' => Http::response(['error' => true, 'message' => 'instância off'], 200),
        ]);

        $n = $this->app->make(FidelidadeOtpNotifier::class);
        $r = $n->tentarEnviarCodigoWhatsapp('11988887777', 'msg');

        $this->assertFalse($r['ok']);
        $this->assertSame(FidelidadeOtpNotifier::RESULTADO_RESPOSTA_ERRO, $r['resultado']);
    }
}
