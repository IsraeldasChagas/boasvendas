<?php

namespace App\Support;

use App\Models\Empresa;
use App\Models\Pedido;

/**
 * Gera link wa.me com texto pronto para avisar o cliente sobre mudança de status do pedido (vitrine).
 */
final class WhatsAppPedidoCliente
{
    public static function normalizarTelefoneBr(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        $d = preg_replace('/\D+/', '', $raw);
        if ($d === null || $d === '') {
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

    /**
     * URL https://wa.me/...?text=... ou null se não for possível (sem telefone, sem slug, etc.).
     */
    public static function urlAvisoStatus(Pedido $pedido, Empresa $empresa, string $statusNovo): ?string
    {
        $canal = $pedido->canal ?? Pedido::CANAL_LOJA;
        if ($canal !== Pedido::CANAL_LOJA) {
            return null;
        }

        $digits = self::normalizarTelefoneBr($pedido->cliente_telefone);
        if ($digits === null) {
            return null;
        }

        $slug = trim((string) ($empresa->slug ?? ''));
        if ($slug === '') {
            return null;
        }

        $codigo = trim((string) $pedido->codigo_publico);
        if ($codigo === '') {
            return null;
        }

        $rotulo = Pedido::statusRotulos()[$statusNovo] ?? $statusNovo;
        $linkPedido = route('publico.pedido.show', ['slug' => $slug, 'codigo' => $codigo], absolute: true);
        $nomeCliente = trim((string) $pedido->cliente_nome);
        $nomeLoja = trim((string) ($empresa->nome ?? 'Loja'));

        // Ícones só via \u{…} — arquivo ASCII evita emoji corrompido (?) no WhatsApp.
        // *…* vira negrito no WhatsApp.
        $iconLoja = "\u{1F6CD}\u{FE0F}"; // 🛍️
        $iconPedido = "\u{1F194}"; // 🆔 ID / código
        $iconStatus = "\u{1F4E6}"; // 📦 situação do pedido
        $iconLink = "\u{1F517}"; // 🔗

        $msg = $iconLoja.' *'.$nomeLoja."*\n\n";
        $msg .= 'Olá';
        if ($nomeCliente !== '') {
            $msg .= ', '.$nomeCliente;
        }
        $msg .= "!\n\n";
        $msg .= $iconPedido.' *Pedido:* *'.$codigo."*\n\n";
        $msg .= $iconStatus.' *Situação:* '.$rotulo."\n\n";
        $msg .= $iconLink." *Acompanhar seu pedido*\n".$linkPedido;

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($msg);
    }
}
