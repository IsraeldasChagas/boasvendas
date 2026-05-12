<?php

namespace App\Support;

use App\Models\Cliente;
use App\Models\FidelidadeCartao;

/**
 * Link wa.me com mensagem pronta (sem API paga). Futuro: substituir por envio automático (Evolution, etc.).
 */
final class FidelidadeCartaoWhatsappLink
{
    /**
     * Dígitos com DDI 55 para wa.me ou null se inválido.
     */
    public static function telefoneInternacional55(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        $d = preg_replace('/\D+/', '', $raw);
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

    /**
     * @param  string  $nome  Nome para saudação na mensagem
     */
    public static function urlPorDados(string $nome, ?string $telefoneRaw, string $codigoFidelidade, int $pontos): ?string
    {
        $codigo = trim($codigoFidelidade);
        if ($codigo === '') {
            return null;
        }

        $digits = self::telefoneInternacional55($telefoneRaw);
        if ($digits === null) {
            return null;
        }

        $nomeLimpo = trim(str_replace('*', '', $nome !== '' ? $nome : 'Cliente'));

        $msg = 'Olá, '.$nomeLimpo."! 🎉\n"
            ."Seu cartão fidelidade do VendaFácil foi criado/atualizado.\n\n"
            .'Código fidelidade: '.$codigo."\n"
            .'Pontos atuais: '.$pontos."\n\n"
            .'Apresente esse código na próxima compra para acumular benefícios.';

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($msg);
    }

    /**
     * URL https://wa.me/55...?text=... ou null (sem telefone válido).
     */
    public static function urlMensagemCartao(Cliente $cliente, FidelidadeCartao $cartao): ?string
    {
        return self::urlPorDados(
            (string) ($cliente->nome ?? 'Cliente'),
            $cliente->telefone,
            (string) ($cartao->codigo_fidelidade ?? ''),
            (int) ($cartao->pontos ?? 0)
        );
    }

    /** Cartão sem cliente vinculado: usa o telefone normalizado (só dígitos) do cartão. */
    public static function urlMensagemCartaoPorTelefone(FidelidadeCartao $cartao): ?string
    {
        return self::urlPorDados(
            'Cliente',
            $cartao->telefone_normalizado,
            (string) ($cartao->codigo_fidelidade ?? ''),
            (int) ($cartao->pontos ?? 0)
        );
    }
}
