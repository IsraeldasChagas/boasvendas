<?php

namespace App\Support;

use App\Models\Adicional;
use App\Models\Empresa;
use App\Models\Pedido;

/**
 * Cupom / comanda em texto (WhatsApp) e base para impressão térmica (HTML).
 */
final class CupomPedidoCliente
{
    private const TEXTO_WHATSAPP_MAX = 3600;

    /** Texto completo do cupom (UTF-8), linha a linha. */
    public static function textoCupomCompleto(Pedido $pedido, Empresa $empresa): string
    {
        $pedido->loadMissing('itens');

        $slug = trim((string) ($empresa->slug ?? ''));
        $lines = [];

        $lines[] = str_repeat('─', 28);
        $lines[] = self::fixUpperNomeLoja(trim((string) ($empresa->nome ?? 'Loja')));
        $lines[] = str_repeat('─', 28);

        $end = trim((string) ($empresa->endereco ?? ''));
        if ($end !== '') {
            $lines[] = $end;
        }
        if (trim((string) ($empresa->cep ?? '')) !== '') {
            $cep = preg_replace('/\D+/', '', (string) $empresa->cep);
            $lines[] = 'CEP '.substr($cep, 0, 5).'-'.substr($cep, 5);
        }
        $waLoja = trim((string) ($empresa->whatsapp ?? ''));
        if ($waLoja !== '') {
            $lines[] = 'WhatsApp: '.$waLoja;
        }
        $cnpj = trim((string) ($empresa->cnpj ?? ''));
        if ($cnpj !== '') {
            $lines[] = 'CNPJ: '.$cnpj;
        }

        $lines[] = '';
        $lines[] = 'PEDIDO '.$pedido->codigo_publico;
        $lines[] = $pedido->created_at->format('d/m/Y H:i');
        $lines[] = 'Status: '.$pedido->rotuloStatus();
        $lines[] = $pedido->rotuloTipoEntrega().' · Loja online';

        $lines[] = '';
        $lines[] = '--- CLIENTE ---';
        $lines[] = $pedido->cliente_nome;
        $lines[] = $pedido->cliente_telefone;
        if (trim((string) ($pedido->cliente_email ?? '')) !== '') {
            $lines[] = $pedido->cliente_email;
        }
        $lines[] = $pedido->endereco;
        if (trim((string) ($pedido->complemento ?? '')) !== '') {
            $lines[] = $pedido->complemento;
        }
        if ($pedido->cep_entrega) {
            $lines[] = 'CEP '.substr($pedido->cep_entrega, 0, 5).'-'.substr($pedido->cep_entrega, 5);
        }

        $lines[] = '';
        $lines[] = '--- ITENS ---';
        foreach ($pedido->itens as $it) {
            $lines[] = $it->nome_produto.' x'.$it->quantidade.'  R$ '.number_format((float) $it->subtotal, 2, ',', '.');
            foreach (self::linhasOpcoesItemTexto($it->opcoes_linha) as $lx) {
                $lines[] = '  '.$lx;
            }
        }

        $lines[] = '';
        $lines[] = 'Subtotal    R$ '.number_format((float) $pedido->subtotal, 2, ',', '.');
        $rotTaxa = ($pedido->tipo_entrega ?? Pedido::TIPO_ENTREGA_ENTREGA) === Pedido::TIPO_ENTREGA_BALCAO
            ? 'Retirada'
            : 'Taxa entrega';
        $lines[] = $rotTaxa.'  R$ '.number_format((float) $pedido->taxa_entrega, 2, ',', '.');
        $lines[] = 'TOTAL       R$ '.number_format((float) $pedido->total, 2, ',', '.');

        $lines[] = '';
        $lines[] = 'PAGAMENTO';
        $lines[] = $pedido->descricaoPagamentoExibicao();

        if (trim((string) ($pedido->observacoes ?? '')) !== '') {
            $lines[] = '';
            $lines[] = 'OBS. DO PEDIDO';
            $lines[] = $pedido->observacoes;
        }

        if ($slug !== '' && $pedido->codigo_publico !== '') {
            $lines[] = '';
            $lines[] = 'ACOMPANHAR PEDIDO';
            $lines[] = route('publico.pedido.show', ['slug' => $slug, 'codigo' => $pedido->codigo_publico], absolute: true);
        }

        $lines[] = '';
        $lines[] = str_repeat('─', 28);
        $lines[] = 'Obrigado pela preferência!';
        $lines[] = str_repeat('─', 28);
        $lines[] = config('app.name');

        return implode("\n", $lines);
    }

    /** wa.me com texto do cupom ou null (telefone inválido). */
    public static function urlWhatsAppCupom(Pedido $pedido, Empresa $empresa): ?string
    {
        $digits = WhatsAppPedidoCliente::normalizarTelefoneBr($pedido->cliente_telefone);
        if ($digits === null) {
            return null;
        }

        $text = self::textoCupomCompleto($pedido, $empresa);
        if (strlen($text) > self::TEXTO_WHATSAPP_MAX) {
            $cut = function_exists('mb_substr')
                ? mb_substr($text, 0, self::TEXTO_WHATSAPP_MAX - 120, 'UTF-8')
                : substr($text, 0, self::TEXTO_WHATSAPP_MAX - 120);
            $text = $cut."\n\n...(mensagem limitada — imprima o cupom completo no painel.)";
        }

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($text);
    }

    /** @return array<int, string> */
    private static function linhasOpcoesItemTexto(?array $opcoesLinha): array
    {
        $out = [];
        $opArr = is_array($opcoesLinha) ? $opcoesLinha : [];
        $lista = is_array($opArr['adicionais'] ?? null) ? $opArr['adicionais'] : [];
        $obsItem = trim((string) ($opArr['observacao'] ?? ''));

        if ($obsItem !== '') {
            $out[] = 'Obs.: '.$obsItem;
        }
        foreach ($lista as $op) {
            $tipo = (string) ($op['tipo'] ?? '');
            $nome = (string) ($op['nome'] ?? '');
            if ($tipo === Adicional::TIPO_RETIRAR || $tipo === 'retirar_ingrediente') {
                $qRet = (int) ($op['quantidade'] ?? 1);
                $out[] = '- '.$nome.($qRet > 1 ? ' x'.$qRet : '');
            } else {
                $qOp = (int) ($op['quantidade'] ?? 1);
                $preco = (float) ($op['preco'] ?? 0);
                $s = '+ '.$nome.($qOp > 1 ? ' x'.$qOp : '');
                if ($preco > 0) {
                    $s .= ' (+R$ '.number_format($preco * max(1, $qOp), 2, ',', '.').')';
                }
                $out[] = $s;
            }
        }

        return $out;
    }

    private static function fixUpperNomeLoja(string $nome): string
    {
        if ($nome === '') {
            return 'LOJA';
        }
        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($nome, 'UTF-8');
        }

        return strtoupper($nome);
    }
}
