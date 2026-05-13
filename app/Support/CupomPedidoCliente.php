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

    /**
     * Texto completo do cupom (UTF-8), linha a linha, com formatação do WhatsApp
     * (*negrito*, _itálico_) na mesma sequência visual do cupom impresso (`imprimir.blade.php`).
     */
    public static function textoCupomCompleto(Pedido $pedido, Empresa $empresa): string
    {
        $pedido->loadMissing('itens');

        $slug = trim((string) ($empresa->slug ?? ''));
        $larguraLinha = 30;
        $lines = [];

        $canal = $pedido->canal ?? Pedido::CANAL_LOJA;
        $subtituloMarca = match ($canal) {
            Pedido::CANAL_BALCAO => 'Pedido balcão',
            Pedido::CANAL_WHATSAPP => 'Pedido WhatsApp',
            default => 'Pedido online',
        };

        $lines[] = self::centralizar($subtituloMarca, $larguraLinha);
        $lines[] = self::centralizar('*'.self::fixUpperNomeLoja(trim((string) ($empresa->nome ?? 'Loja'))).'*', $larguraLinha);

        $end = trim((string) ($empresa->endereco ?? ''));
        if ($end !== '') {
            $lines[] = self::centralizar($end, $larguraLinha);
        }
        if (trim((string) ($empresa->cep ?? '')) !== '') {
            $cep = preg_replace('/\D+/', '', (string) $empresa->cep);
            if (strlen((string) $cep) === 8) {
                $lines[] = self::centralizar('CEP '.substr($cep, 0, 5).'-'.substr($cep, 5), $larguraLinha);
            }
        }
        $waLoja = trim((string) ($empresa->whatsapp ?? ''));
        if ($waLoja !== '') {
            $lines[] = self::centralizar('WhatsApp: '.$waLoja, $larguraLinha);
        }
        $cnpj = trim((string) ($empresa->cnpj ?? ''));
        if ($cnpj !== '') {
            $lines[] = self::centralizar('CNPJ '.$cnpj, $larguraLinha);
        }

        $lines[] = self::linhaTracejada($larguraLinha);
        $lines[] = self::centralizar('Cupom simplificado / comanda', $larguraLinha);
        $lines[] = self::centralizar('*'.$pedido->codigo_publico.'*', $larguraLinha);
        $lines[] = self::centralizar(
            $pedido->created_at->format('d/m/Y H:i').' · '.$pedido->rotuloStatus().' · '.$pedido->rotuloTipoEntrega(),
            $larguraLinha
        );
        $lines[] = self::linhaTracejada($larguraLinha);

        $lines[] = '*CLIENTE E ENTREGA*';
        if (trim((string) ($pedido->cliente_nome ?? '')) !== '') {
            $lines[] = $pedido->cliente_nome;
        }
        if (trim((string) ($pedido->cliente_telefone ?? '')) !== '') {
            $lines[] = $pedido->cliente_telefone;
        }
        if (trim((string) ($pedido->cliente_email ?? '')) !== '') {
            $lines[] = $pedido->cliente_email;
        }
        if (trim((string) ($pedido->endereco ?? '')) !== '') {
            $lines[] = $pedido->endereco;
        }
        if (trim((string) ($pedido->complemento ?? '')) !== '') {
            $lines[] = $pedido->complemento;
        }
        if ($pedido->cep_entrega) {
            $lines[] = 'CEP '.substr($pedido->cep_entrega, 0, 5).'-'.substr($pedido->cep_entrega, 5);
        }

        $lines[] = '';
        $lines[] = '*ITENS*';
        foreach ($pedido->itens as $it) {
            $nomeQtd = $it->nome_produto.' × '.$it->quantidade;
            $valor = 'R$ '.number_format((float) $it->subtotal, 2, ',', '.');
            $lines[] = self::linhaDuasColunas($nomeQtd, $valor, $larguraLinha);
            foreach (self::linhasOpcoesItemTexto($it->opcoes_linha) as $lx) {
                $lines[] = '  '.$lx;
            }
        }

        $lines[] = self::linhaTracejada($larguraLinha);
        $lines[] = self::linhaDuasColunas('Subtotal', 'R$ '.number_format((float) $pedido->subtotal, 2, ',', '.'), $larguraLinha);
        $rotTaxa = ($pedido->tipo_entrega ?? Pedido::TIPO_ENTREGA_ENTREGA) === Pedido::TIPO_ENTREGA_BALCAO
            ? 'Retirada (sem frete)'
            : 'Taxa de entrega';
        $lines[] = self::linhaDuasColunas($rotTaxa, 'R$ '.number_format((float) $pedido->taxa_entrega, 2, ',', '.'), $larguraLinha);
        $lines[] = self::linhaDuasColunas('*TOTAL*', '*R$ '.number_format((float) $pedido->total, 2, ',', '.').'*', $larguraLinha);

        $lines[] = '';
        $lines[] = '*PAGAMENTO*';
        $lines[] = $pedido->descricaoPagamentoExibicao();

        if (trim((string) ($pedido->observacoes ?? '')) !== '') {
            $lines[] = '';
            $lines[] = '*OBSERVAÇÕES*';
            $lines[] = $pedido->observacoes;
        }

        if ($slug !== '' && $pedido->codigo_publico !== '') {
            $lines[] = '';
            $lines[] = '*ACOMPANHAR PEDIDO*';
            $lines[] = route('publico.pedido.show', ['slug' => $slug, 'codigo' => $pedido->codigo_publico], absolute: true);
        }

        $lines[] = self::linhaTracejada($larguraLinha);
        $lines[] = self::centralizar('Obrigado pela preferência!', $larguraLinha);
        $lines[] = self::centralizar('_'.config('app.name').'_', $larguraLinha);

        return implode("\n", $lines);
    }

    private static function linhaTracejada(int $largura): string
    {
        return str_repeat('-', max(8, $largura));
    }

    private static function centralizar(string $texto, int $largura): string
    {
        $tam = self::larguraVisual($texto);
        if ($tam >= $largura) {
            return $texto;
        }
        $pad = (int) floor(($largura - $tam) / 2);

        return str_repeat(' ', max(0, $pad)).$texto;
    }

    private static function linhaDuasColunas(string $esq, string $dir, int $largura): string
    {
        $tamEsq = self::larguraVisual($esq);
        $tamDir = self::larguraVisual($dir);
        $espacos = $largura - $tamEsq - $tamDir;
        if ($espacos < 1) {
            $espacos = 1;
        }

        return $esq.str_repeat(' ', $espacos).$dir;
    }

    /**
     * Largura visual aproximada do texto desconsiderando marcadores Markdown
     * do WhatsApp (`*negrito*`, `_itálico_`, `~tachado~`).
     */
    private static function larguraVisual(string $texto): int
    {
        $semMarca = preg_replace('/(?<!\\\\)([\*_~])(.+?)(?<!\\\\)\\1/u', '$2', $texto) ?? $texto;
        if (function_exists('mb_strlen')) {
            return (int) mb_strlen($semMarca, 'UTF-8');
        }

        return strlen($semMarca);
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
