<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envia o código de verificação da fidelidade via webhook HTTP (WhatsApp Business API,
 * Evolution API, Z-API, n8n/Make, etc.). Sem URL configurada: em local/testing considera
 * envio simulado com sucesso (log); em produção retorna false.
 */
final class FidelidadeOtpNotifier
{
    public function enviarCodigoWhatsapp(string $telefoneSomenteDigitos, string $mensagemTexto): bool
    {
        $internacional = $this->telefoneInternacionalBr($telefoneSomenteDigitos);
        if ($internacional === null) {
            return false;
        }

        $url = trim((string) config('services.fidelidade_otp.notify_url'));
        if ($url === '') {
            if (app()->environment('local', 'testing')) {
                Log::info('[fidelidade-otp] WhatsApp simulado (defina FIDELIDADE_OTP_NOTIFY_URL em produção)', [
                    'telefone' => $internacional,
                    'mensagem' => $mensagemTexto,
                ]);

                return true;
            }

            return false;
        }

        $phoneKey = (string) config('services.fidelidade_otp.json_phone_key', 'phone');
        $messageKey = (string) config('services.fidelidade_otp.json_message_key', 'message');
        $payload = [
            $phoneKey => $internacional,
            $messageKey => $mensagemTexto,
        ];

        $token = trim((string) config('services.fidelidade_otp.notify_bearer'));
        $request = Http::timeout(20)->acceptJson();
        if ($token !== '') {
            $request = $request->withToken($token);
        }

        $response = $request->asJson()->post($url, $payload);
        if (! $response->successful()) {
            Log::warning('[fidelidade-otp] Falha ao notificar WhatsApp', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    /** Dígitos com código do país 55, ou null se inválido. */
    public function telefoneInternacionalBr(string $digits): ?string
    {
        $d = preg_replace('/\D+/', '', $digits);
        if ($d === '' || $d === null) {
            return null;
        }
        if (strlen($d) === 10 || strlen($d) === 11) {
            $d = '55'.$d;
        }
        if (strlen($d) < 12 || strlen($d) > 15) {
            return null;
        }

        return $d;
    }
}
