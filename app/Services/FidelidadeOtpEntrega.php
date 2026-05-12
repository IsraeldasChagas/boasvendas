<?php

namespace App\Services;

use App\Mail\FidelidadeOtpMail;
use App\Models\Empresa;
use App\Models\FidelidadeCartao;
use App\Support\FidelidadeCartaoWhatsappLink;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Envia o código OTP: tenta WhatsApp (webhook/Evolution); opcionalmente e-mail do cartão;
 * por fim link wa.me com o texto do código (sem API paga) quando {@see config('services.fidelidade_otp.wa_me_fallback')}.
 */
class FidelidadeOtpEntrega
{
    public const CANAL_WHATSAPP = 'whatsapp';

    public const CANAL_EMAIL = 'email';

    /** Código disponível via link wa.me (sem webhook / Evolution). */
    public const CANAL_WAME = 'whatsapp_link';

    public const FALHA_EMAIL = 'email_falhou';

    /** WhatsApp falhou e o fallback por e-mail está desligado. */
    public const FALHA_WHATSAPP = 'whatsapp_falhou';

    public const FALHA_SEM_DESTINO = 'sem_destino';

    public function __construct(
        private readonly FidelidadeOtpNotifier $whatsapp,
    ) {}

    /**
     * @return array{ok: bool, canal?: string, resultado?: string, wa?: array, wa_me_url?: string}
     */
    public function entregar(Empresa $empresa, string $telNorm, string $codigo, int $ttlMinutos): array
    {
        $nomeLoja = trim((string) ($empresa->nome ?? 'Loja'));
        $msgWa = '['.$nomeLoja.'] Seu código para ver o cartão fidelidade: '.$codigo.'. Válido por '.$ttlMinutos.' minutos. Não compartilhe com ninguém.';

        $wa = $this->whatsapp->tentarEnviarCodigoWhatsapp($telNorm, $msgWa);
        if ($wa['ok']) {
            return ['ok' => true, 'canal' => self::CANAL_WHATSAPP];
        }

        $emailResult = null;
        if ((bool) config('services.fidelidade_otp.email_fallback', false)) {
            Log::notice('[fidelidade-otp] WhatsApp não enviado; tentando e-mail do cartão', [
                'empresa_id' => $empresa->id,
                'wa_resultado' => $wa['resultado'] ?? null,
                'tel_sufixo' => strlen($telNorm) >= 4 ? substr($telNorm, -4) : null,
            ]);

            $emailResult = $this->entregarSomenteEmail($empresa->id, $telNorm, $codigo, $ttlMinutos, $nomeLoja);
            if ($emailResult['ok']) {
                return $emailResult;
            }
        }

        $waMeUrl = $this->montarUrlWaMeOtp($telNorm, $msgWa);
        if ($waMeUrl !== null) {
            Log::notice('[fidelidade-otp] WhatsApp via API indisponível ou falhou; usando link wa.me (sem Evolution).', [
                'empresa_id' => $empresa->id,
                'wa_resultado' => $wa['resultado'] ?? null,
                'tel_sufixo' => strlen($telNorm) >= 4 ? substr($telNorm, -4) : null,
            ]);

            return ['ok' => true, 'canal' => self::CANAL_WAME, 'wa_me_url' => $waMeUrl, 'wa' => $wa];
        }

        if (! (bool) config('services.fidelidade_otp.email_fallback', false)) {
            Log::warning('[fidelidade-otp] WhatsApp não enviado e link wa.me indisponível (telefone inválido ou FIDELIDADE_OTP_WAME_FALLBACK=false).', [
                'empresa_id' => $empresa->id,
                'wa_resultado' => $wa['resultado'] ?? null,
                'tel_sufixo' => strlen($telNorm) >= 4 ? substr($telNorm, -4) : null,
            ]);

            return ['ok' => false, 'resultado' => self::FALHA_WHATSAPP, 'wa' => $wa];
        }

        if (($emailResult['resultado'] ?? '') === self::FALHA_SEM_DESTINO) {
            return ['ok' => false, 'resultado' => self::FALHA_SEM_DESTINO, 'wa' => $wa];
        }

        return $emailResult ?? ['ok' => false, 'resultado' => self::FALHA_WHATSAPP, 'wa' => $wa];
    }

    private function montarUrlWaMeOtp(string $telNorm, string $mensagemTexto): ?string
    {
        if (! (bool) config('services.fidelidade_otp.wa_me_fallback', true)) {
            return null;
        }

        return FidelidadeCartaoWhatsappLink::urlTextoParaTelefone($telNorm, $mensagemTexto);
    }

    /**
     * @return array{ok: bool, canal?: string, resultado?: string}
     */
    private function entregarSomenteEmail(int $empresaId, string $telNorm, string $codigo, int $ttlMinutos, string $nomeLoja): array
    {
        $email = $this->emailDoCartao($empresaId, $telNorm);
        if ($email === null) {
            return ['ok' => false, 'resultado' => self::FALHA_SEM_DESTINO];
        }

        $corpo = $this->corpoEmail($nomeLoja, $codigo, $ttlMinutos);
        try {
            Mail::to($email)->send(new FidelidadeOtpMail($corpo, $nomeLoja));
            Log::info('[fidelidade-otp] Código enviado por e-mail', [
                'empresa_id' => $empresaId,
                'tel_sufixo' => strlen($telNorm) >= 4 ? substr($telNorm, -4) : null,
            ]);

            return ['ok' => true, 'canal' => self::CANAL_EMAIL];
        } catch (Throwable $e) {
            Log::error('[fidelidade-otp] Falha ao enviar e-mail com o código', [
                'empresa_id' => $empresaId,
                'exception' => $e->getMessage(),
            ]);

            return ['ok' => false, 'resultado' => self::FALHA_EMAIL];
        }
    }

    private function emailDoCartao(int $empresaId, string $telNorm): ?string
    {
        if (! Schema::hasColumn('fidelidade_cartoes', 'email')) {
            return null;
        }
        $cartao = FidelidadeCartao::query()
            ->where('empresa_id', $empresaId)
            ->where('telefone_normalizado', $telNorm)
            ->first(['email']);
        if ($cartao === null) {
            return null;
        }
        $email = strtolower(trim((string) ($cartao->email ?? '')));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    private function corpoEmail(string $nomeLoja, string $codigo, int $ttlMinutos): string
    {
        return "Olá,\n\n"
            .'Seu código para consultar o cartão fidelidade na loja «'.$nomeLoja.'» é: '.$codigo."\n\n"
            .'Ele vale por '.$ttlMinutos." minutos. Não encaminhe este código.\n\n"
            ."Se você não pediu este código, ignore este e-mail.\n";
    }
}
