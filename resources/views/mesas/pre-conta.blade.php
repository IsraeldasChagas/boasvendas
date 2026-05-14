<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Pré-conta — Mesa #{{ $comanda->mesa?->numero }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body class="p-4">
<div class="no-print mb-3">
    <button type="button" class="btn btn-primary" onclick="window.print()">Imprimir</button>
    <a href="{{ route('empresa.comandas.show', $comanda) }}" class="btn btn-outline-secondary">Voltar</a>
</div>
<h1 class="h4">Pré-conta</h1>
<p class="mb-1">Mesa #{{ $comanda->mesa?->numero }} {{ $comanda->mesa?->nome }} · {{ now()->format('d/m/Y H:i') }}</p>
@if ($comanda->garcom)
    <p class="small text-muted">Garçom: {{ $comanda->garcom->name }}</p>
@endif
<table class="table table-sm mt-3">
    <thead><tr><th>Item</th><th class="text-end">Valor</th></tr></thead>
    <tbody>
        @foreach ($comanda->itens as $it)
            @continue($it->status === \App\Enums\Mesas\ComandaItemStatus::Cancelado)
            <tr>
                <td>{{ $it->nome_produto }} × {{ $it->quantidade }}</td>
                <td class="text-end">R$ {{ number_format((float) $it->valor_total, 2, ',', '.') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<p class="text-end"><strong>Subtotal:</strong> R$ {{ number_format((float) $comanda->subtotal, 2, ',', '.') }}</p>
<p class="text-end">Taxa: R$ {{ number_format((float) $comanda->taxa_servico, 2, ',', '.') }} · Desconto: R$ {{ number_format((float) $comanda->desconto, 2, ',', '.') }}</p>
<p class="text-end fs-4"><strong>Total:</strong> R$ {{ number_format((float) $comanda->total, 2, ',', '.') }}</p>
</body>
</html>
