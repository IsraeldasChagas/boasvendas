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
        $nomeLojaEsc = str_replace('*', '', $nomeLoja);
        $nomeClienteEsc = str_replace('*', '', $nomeCliente);
        $codigoEsc = str_replace('*', '', $codigo);

        // Ícones só via \u{…} — arquivo ASCII evita emoji corrompido (?) no WhatsApp.
        // Ordem: casa (loja só nome) → boneco (cliente) → sacola (código) → risco (status) → lupa (acompanhar).
        $iconLoja = "\u{1F3E0}"; // 🏠 casa
        $iconCliente = "\u{1F464}"; // 👤 boneco / pessoa
        $iconSacola = "\u{1F6CD}\u{FE0F}"; // 🛍️ sacola / código
        $iconRisco = "\u{2796}"; // ➖ traço (status)
        $iconLupa = "\u{1F50D}"; // 🔍 lupa / acompanhar

        $msg = $iconLoja.' *'.$nomeLojaEsc."*\n\n";
        $msg .= $iconCliente.' ';
        if ($nomeClienteEsc !== '') {
            $msg .= '*'.$nomeClienteEsc.'*';
        } else {
            $msg .= 'Cliente';
        }
        $msg .= "\n\n";
        $msg .= $iconSacola.' *'.$codigoEsc."*\n\n";
        $msg .= $iconRisco.' '.$rotulo."\n\n";
        $msg .= $iconLupa." Acompanhar seu pedido\n".$linkPedido;

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($msg);
    }
}
