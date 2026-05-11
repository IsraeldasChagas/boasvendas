<?php

namespace App\Services;

use App\Mail\FidelidadeOtpMail;
use App\Models\Empresa;
use App\Models\FidelidadeCartao;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Entrega o código OTP da fidelidade: tenta WhatsApp (webhook); se não houver ou falhar, envia por e-mail do cartão.
 */
class FidelidadeOtpEntrega
{
    public const CANAL_WHATSAPP = 'whatsapp';

    public const CANAL_EMAIL = 'email';

    public const FALHA_EMAIL = 'email_falhou';

    public const FALHA_SEM_DESTINO = 'sem_destino';

    public function __construct(
        private readonly FidelidadeOtpNotifier $whatsapp,
    ) {}

    /**
     * @return array{ok: bool, canal?: string, resultado?: string}
     */
    public function entregar(Empresa $empresa, string $telNorm, string $codigo, int $ttlMinutos): array
    {
        $nomeLoja = trim((string) ($empresa->nome ?? 'Loja'));
        $msgWa = '['.$nomeLoja.'] Seu código para ver o cartão fidelidade: '.$codigo.'. Válido por '.$ttlMinutos.' minutos. Não compartilhe com ninguém.';

        $wa = $this->whatsapp->tentarEnviarCodigoWhatsapp($telNorm, $msgWa);
        if ($wa['ok']) {
            return ['ok' => true, 'canal' => self::CANAL_WHATSAPP];
        }

        $email = $this->emailDoCartao($empresa->id, $telNorm);
        if ($email !== null) {
            $corpo = $this->corpoEmail($nomeLoja, $codigo, $ttlMinutos);
            try {
                Mail::to($email)->send(new FidelidadeOtpMail($corpo, $nomeLoja));
                Log::info('[fidelidade-otp] Código enviado por e-mail', [
                    'empresa_id' => $empresa->id,
                    'tel_sufixo' => strlen($telNorm) >= 4 ? substr($telNorm, -4) : null,
                ]);

                return ['ok' => true, 'canal' => self::CANAL_EMAIL];
            } catch (Throwable $e) {
                Log::error('[fidelidade-otp] Falha ao enviar e-mail com o código', [
                    'empresa_id' => $empresa->id,
                    'exception' => $e->getMessage(),
                ]);

                return ['ok' => false, 'resultado' => self::FALHA_EMAIL];
            }
        }

        return ['ok' => false, 'resultado' => self::FALHA_SEM_DESTINO, 'wa' => $wa];
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
