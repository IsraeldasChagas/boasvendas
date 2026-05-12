<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Envia o código de verificação da fidelidade via webhook HTTP (WhatsApp Business API,
 * Evolution API v2, Z-API, n8n/Make, etc.).
 *
 * Padrão alinhado à Evolution API v2 (ex.: preventivos / sas-estoque): POST JSON com
 * {@see config('services.fidelidade_otp.json_phone_key')} (number) e
 * {@see config('services.fidelidade_otp.json_message_key')} (text), autenticação tipicamente header "apikey".
 */
class FidelidadeOtpNotifier
{
    public const RESULTADO_OK = 'ok';

    public const RESULTADO_SEM_WEBHOOK = 'sem_webhook';

    public const RESULTADO_TELEFONE_INVALIDO = 'telefone_invalido';

    public const RESULTADO_REDE = 'rede';

    public const RESULTADO_HTTP = 'http_nao_sucesso';

    public const RESULTADO_RESPOSTA_ERRO = 'resposta_indica_erro';

    /**
     * @return array{ok: bool, resultado: string, http_status?: int}
     */
    public function tentarEnviarCodigoWhatsapp(string $telefoneSomenteDigitos, string $mensagemTexto): array
    {
        $internacional = $this->telefoneInternacionalBr($telefoneSomenteDigitos);
        if ($internacional === null) {
            Log::notice('[fidelidade-otp] Telefone inválido para envio internacional', [
                'entrada' => $telefoneSomenteDigitos,
            ]);

            return ['ok' => false, 'resultado' => self::RESULTADO_TELEFONE_INVALIDO];
        }

        $url = trim((string) config('services.fidelidade_otp.notify_url'));
        $simular = (bool) config('services.fidelidade_otp.simulate_without_url', false);

        if ($url === '') {
            if ($simular || app()->environment('local', 'testing')) {
                Log::info('[fidelidade-otp] WhatsApp simulado (sem FIDELIDADE_OTP_NOTIFY_URL ou ambiente local)', [
                    'telefone' => $internacional,
                    'mensagem' => $mensagemTexto,
                    'simular_config' => $simular,
                ]);

                return ['ok' => true, 'resultado' => self::RESULTADO_OK];
            }

            Log::warning('[fidelidade-otp] FIDELIDADE_OTP_NOTIFY_URL vazio em produção; envio abortado.');

            return ['ok' => false, 'resultado' => self::RESULTADO_SEM_WEBHOOK];
        }

        $phoneKey = (string) config('services.fidelidade_otp.json_phone_key', 'number');
        $messageKey = (string) config('services.fidelidade_otp.json_message_key', 'text');
        $payload = array_merge($this->jsonExtraDecodificado(), [
            $phoneKey => $internacional,
            $messageKey => $mensagemTexto,
        ]);

        $token = trim((string) config('services.fidelidade_otp.notify_bearer'));
        $authType = strtolower((string) config('services.fidelidade_otp.notify_auth_type', 'bearer'));

        $verifySsl = (bool) config('services.fidelidade_otp.verify_ssl', true);
        $request = Http::timeout(25)->acceptJson()->withOptions(['verify' => $verifySsl]);
        if ($token !== '') {
            if ($authType === 'apikey') {
                $header = (string) config('services.fidelidade_otp.notify_apikey_header', 'apikey');
                $header = $header !== '' ? $header : 'apikey';
                $request = $request->withHeaders([$header => $token]);
            } elseif ($authType !== 'none') {
                $request = $request->withToken($token);
            }
        }

        try {
            $response = $request->asJson()->post($url, $payload);
        } catch (Throwable $e) {
            Log::error('[fidelidade-otp] Erro de rede ao chamar webhook', [
                'url' => $url,
                'exception' => $e->getMessage(),
            ]);

            return ['ok' => false, 'resultado' => self::RESULTADO_REDE];
        }

        $status = $response->status();
        if (! $response->successful()) {
            Log::warning('[fidelidade-otp] Webhook retornou erro', [
                'status' => $status,
                'body' => mb_substr($response->body(), 0, 2000),
            ]);

            return ['ok' => false, 'resultado' => self::RESULTADO_HTTP, 'http_status' => $status];
        }

        if ($this->corpoJsonIndicaErro($response->body())) {
            Log::warning('[fidelidade-otp] Webhook HTTP 2xx mas corpo indica falha', [
                'status' => $status,
                'body' => mb_substr($response->body(), 0, 2000),
            ]);

            return ['ok' => false, 'resultado' => self::RESULTADO_RESPOSTA_ERRO, 'http_status' => $status];
        }

        Log::info('[fidelidade-otp] Webhook WhatsApp OK', [
            'status' => $status,
            'tel_sufixo' => strlen($internacional) >= 4 ? substr($internacional, -4) : null,
            'body_preview' => mb_substr($response->body(), 0, 500),
        ]);

        return ['ok' => true, 'resultado' => self::RESULTADO_OK];
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonExtraDecodificado(): array
    {
        $raw = trim((string) config('services.fidelidade_otp.json_extra', ''));
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            Log::warning('[fidelidade-otp] FIDELIDADE_OTP_JSON_EXTRA ignorado (JSON inválido).');

            return [];
        }

        return $decoded;
    }

    private function corpoJsonIndicaErro(string $body): bool
    {
        $trim = trim($body);
        if ($trim === '' || (! str_starts_with($trim, '{') && ! str_starts_with($trim, '['))) {
            return false;
        }
        $data = json_decode($body, true);
        if (! is_array($data)) {
            return false;
        }
        if (array_key_exists('error', $data) && $data['error'] === true) {
            return true;
        }
        if (isset($data['status']) && is_string($data['status']) && strtoupper((string) $data['status']) === 'ERROR') {
            return true;
        }

        return false;
    }

    /** Dígitos com código do país 55, ou null se inválido. */
    public function telefoneInternacionalBr(string $digits): ?string
    {
        $d = preg_replace('/\D+/', '', $digits);
        if ($d === '' || $d === null) {
            return null;
        }
        while (strlen($d) > 11 && str_starts_with($d, '0')) {
            $d = substr($d, 1);
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
