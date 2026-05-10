<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pedido {{ $pedido->codigo_publico }} — {{ $empresa->nome }}</title>
    <style>
        :root { font-family: system-ui, Segoe UI, sans-serif; }
        body { margin: 0; padding: 12px; max-width: 80mm; color: #111; font-size: 12px; line-height: 1.35; }
        h1 { font-size: 14px; margin: 0 0 8px; }
        .muted { color: #555; font-size: 11px; }
        .mono { font-family: ui-monospace, monospace; letter-spacing: 0.02em; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        td { padding: 4px 0; vertical-align: top; }
        .tot { border-top: 1px dashed #999; margin-top: 8px; padding-top: 8px; }
        .no-print { margin-top: 16px; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 4px; }
        }
    </style>
</head>
<body>
    <h1>{{ $empresa->nome }}</h1>
    <p class="muted mb-1">Pedido <span class="mono fw-bold">{{ $pedido->codigo_publico }}</span></p>
    <p class="muted mb-0">{{ $pedido->created_at->format('d/m/Y H:i') }} · {{ $pedido->rotuloTipoEntrega() }}</p>

    <p class="mt-3 mb-1"><strong>{{ $pedido->cliente_nome }}</strong></p>
    <p class="muted mb-0">{{ $pedido->cliente_telefone }}</p>
    @if ($pedido->cliente_email)
        <p class="muted mb-0">{{ $pedido->cliente_email }}</p>
    @endif
    <p class="mt-2 mb-0">{{ $pedido->endereco }}@if ($pedido->complemento), {{ $pedido->complemento }}@endif</p>
    @if ($pedido->cep_entrega)
        <p class="muted mb-0">CEP {{ substr($pedido->cep_entrega, 0, 5) }}-{{ substr($pedido->cep_entrega, 5) }}</p>
    @endif

    <table>
        <tbody>
            @foreach ($pedido->itens as $it)
                <tr>
                    <td>{{ $it->nome_produto }} × {{ $it->quantidade }}</td>
                    <td class="text-end" style="white-space:nowrap">R$ {{ number_format((float) $it->subtotal, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="tot">
        <table>
            <tr><td>Subtotal</td><td class="text-end">R$ {{ number_format((float) $pedido->subtotal, 2, ',', '.') }}</td></tr>
            <tr>
                <td>{{ ($pedido->tipo_entrega ?? \App\Models\Pedido::TIPO_ENTREGA_ENTREGA) === \App\Models\Pedido::TIPO_ENTREGA_BALCAO ? 'Retirada' : 'Taxa de entrega' }}</td>
                <td class="text-end">R$ {{ number_format((float) $pedido->taxa_entrega, 2, ',', '.') }}</td>
            </tr>
            <tr><td><strong>Total</strong></td><td class="text-end"><strong>R$ {{ number_format((float) $pedido->total, 2, ',', '.') }}</strong></td></tr>
        </table>
    </div>

    <p class="mt-2 mb-0"><strong>Pagamento:</strong> {{ $pedido->descricaoPagamentoExibicao() }}</p>
    @if ($pedido->observacoes)
        <p class="mt-2 mb-0"><strong>Obs.:</strong> {{ $pedido->observacoes }}</p>
    @endif

    <div class="no-print">
        <button type="button" onclick="window.print()" style="padding:8px 16px;font-size:14px;cursor:pointer">Imprimir</button>
    </div>
    <script>
        if (new URLSearchParams(location.search).get('auto') === '1') {
            window.addEventListener('load', function () { window.print(); });
        }
    </script>
</body>
</html>
