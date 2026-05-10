<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cupom {{ $pedido->codigo_publico }} — {{ $empresa->nome }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous">
    <style>
        @page { size: 80mm auto; margin: 5mm; }
        :root {
            font-family: ui-sans-serif, system-ui, "Segoe UI", Roboto, sans-serif;
            font-size: 11px;
            line-height: 1.35;
            color: #111;
        }
        body {
            margin: 0 auto;
            padding: 10px 8px 24px;
            max-width: 72mm;
        }
        .cupom-head { text-align: center; margin-bottom: 8px; }
        .cupom-logo {
            display: block;
            margin: 0 auto 8px;
            max-width: 46mm;
            max-height: 22mm;
            object-fit: contain;
        }
        .cupom-marca {
            font-size: 10px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #444;
            margin: 0 0 2px;
        }
        .cupom-nome {
            font-size: 14px;
            font-weight: 800;
            margin: 0 0 6px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            line-height: 1.2;
        }
        .cupom-meta { font-size: 10px; color: #444; margin: 2px 0; }
        hr.sep {
            border: none;
            border-top: 1px dashed #555;
            margin: 10px 0;
        }
        .cupom-sec {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.1em;
            margin: 12px 0 6px;
            color: #333;
        }
        .cupom-codigo {
            font-family: ui-monospace, monospace;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.06em;
            margin: 4px 0 8px;
            text-align: center;
        }
        .item-row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            align-items: flex-start;
            padding: 6px 0;
            border-bottom: 1px dotted #ccc;
        }
        .item-row:last-of-type { border-bottom: none; }
        .item-nome { flex: 1; min-width: 0; font-weight: 600; }
        .item-val { white-space: nowrap; font-variant-numeric: tabular-nums; }
        .item-opcoes { margin-top: 4px; font-size: 10px; color: #444; }
        table.tot { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
        table.tot td { padding: 3px 0; vertical-align: top; }
        table.tot td:last-child { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        table.tot tr.total td { font-weight: 800; font-size: 13px; padding-top: 8px; border-top: 2px solid #111; }
        .cupom-link { word-break: break-all; font-size: 9px; margin-top: 6px; }
        .cupom-rodape {
            text-align: center;
            font-size: 10px;
            color: #555;
            margin-top: 14px;
            padding-top: 10px;
            border-top: 1px dashed #999;
        }
        .no-print { margin-top: 20px; text-align: center; display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; }
        .no-print button, .no-print a.btn-link {
            padding: 10px 16px;
            font-size: 14px;
            cursor: pointer;
            border-radius: 8px;
            border: 1px solid #333;
            background: #fff;
            text-decoration: none;
            color: #111;
            display: inline-block;
        }
        .no-print .wa { background: #25d366; border-color: #128c7e; color: #fff; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="cupom-head">
        @if ($empresa->urlLogo())
            <img src="{{ url($empresa->urlLogo()) }}" alt="" class="cupom-logo" width="180" height="80">
        @endif
        <p class="cupom-marca mb-0">Pedido online</p>
        <h1 class="cupom-nome">{{ $empresa->nome }}</h1>
        @if (trim((string) ($empresa->endereco ?? '')))
            <p class="cupom-meta mb-0">{{ $empresa->endereco }}</p>
        @endif
        @php $cepE = preg_replace('/\D+/', '', (string) ($empresa->cep ?? '')); @endphp
        @if (strlen($cepE) === 8)
            <p class="cupom-meta mb-0">CEP {{ substr($cepE, 0, 5) }}-{{ substr($cepE, 5) }}</p>
        @endif
        @if (trim((string) ($empresa->whatsapp ?? '')))
            <p class="cupom-meta mb-0">WhatsApp loja: {{ $empresa->whatsapp }}</p>
        @endif
        @if (trim((string) ($empresa->cnpj ?? '')))
            <p class="cupom-meta mb-0">CNPJ {{ $empresa->cnpj }}</p>
        @endif
    </div>

    <hr class="sep">

    <div class="cupom-meta text-center" style="text-align:center">Cupom fiscal simplificado / comanda</div>
    <div class="cupom-codigo">{{ $pedido->codigo_publico }}</div>
    <p class="cupom-meta mb-0" style="text-align:center">
        {{ $pedido->created_at->format('d/m/Y H:i') }}
        · {{ $pedido->rotuloStatus() }}
        · {{ $pedido->rotuloTipoEntrega() }}
    </p>

    <hr class="sep">

    <div class="cupom-sec">Cliente e entrega</div>
    <p class="mb-1"><strong>{{ $pedido->cliente_nome }}</strong></p>
    <p class="cupom-meta mb-0">{{ $pedido->cliente_telefone }}</p>
    @if (trim((string) ($pedido->cliente_email ?? '')))
        <p class="cupom-meta mb-0">{{ $pedido->cliente_email }}</p>
    @endif
    <p class="mb-0 mt-2">{{ $pedido->endereco }}@if ($pedido->complemento), {{ $pedido->complemento }}@endif</p>
    @if ($pedido->cep_entrega)
        <p class="cupom-meta mb-0">CEP {{ substr($pedido->cep_entrega, 0, 5) }}-{{ substr($pedido->cep_entrega, 5) }}</p>
    @endif

    <div class="cupom-sec">Itens</div>
    @foreach ($pedido->itens as $it)
        <div class="item-row">
            <div class="item-nome">
                {{ $it->nome_produto }} × {{ $it->quantidade }}
                <div class="item-opcoes">
                    @include('partials.opcoes-pedido-item', ['opcoesLinha' => $it->opcoes_linha])
                </div>
            </div>
            <div class="item-val">R$ {{ number_format((float) $it->subtotal, 2, ',', '.') }}</div>
        </div>
    @endforeach

    <table class="tot">
        <tr>
            <td>Subtotal</td>
            <td>R$ {{ number_format((float) $pedido->subtotal, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>{{ ($pedido->tipo_entrega ?? \App\Models\Pedido::TIPO_ENTREGA_ENTREGA) === \App\Models\Pedido::TIPO_ENTREGA_BALCAO ? 'Retirada (sem frete)' : 'Taxa de entrega' }}</td>
            <td>R$ {{ number_format((float) $pedido->taxa_entrega, 2, ',', '.') }}</td>
        </tr>
        <tr class="total">
            <td>Total</td>
            <td>R$ {{ number_format((float) $pedido->total, 2, ',', '.') }}</td>
        </tr>
    </table>

    <div class="cupom-sec">Pagamento</div>
    <p class="mb-0">{{ $pedido->descricaoPagamentoExibicao() }}</p>

    @if (trim((string) ($pedido->observacoes ?? '')))
        <div class="cupom-sec">Observações</div>
        <p class="mb-0">{{ $pedido->observacoes }}</p>
    @endif

    @if ($empresa->slug && $pedido->codigo_publico)
        <div class="cupom-sec">Acompanhar pedido</div>
        <p class="cupom-link mb-0">{{ route('publico.pedido.show', ['slug' => $empresa->slug, 'codigo' => $pedido->codigo_publico], absolute: true) }}</p>
    @endif

    <div class="cupom-rodape">
        Obrigado pela preferência!<br>
        {{ config('app.name') }}
    </div>

    @php
        $waCupom = \App\Support\CupomPedidoCliente::urlWhatsAppCupom($pedido, $empresa);
    @endphp
    <div class="no-print">
        <button type="button" onclick="window.print()">Imprimir na térmica</button>
        @if ($waCupom)
            <a class="wa" href="{{ $waCupom }}" target="_blank" rel="noopener noreferrer">Enviar cupom no WhatsApp</a>
        @endif
        <button type="button" onclick="window.close()">Fechar</button>
    </div>
    <script>
        if (new URLSearchParams(location.search).get('auto') === '1') {
            window.addEventListener('load', function () { window.print(); });
        }
    </script>
</body>
</html>
