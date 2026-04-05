@extends('layouts.print')

@section('title', 'Conferência de caixa')

@section('content')
    <h1 class="h4 fw-bold">Conferência de caixa</h1>
    <p class="text-muted small mb-4">{{ $empresa->nome }} · Aberto em {{ $turno->aberto_em->format('d/m/Y H:i') }}</p>

    <table class="table table-sm table-bordered">
        <tbody>
            <tr><th class="w-50">Fundo de abertura</th><td class="text-end">R$ {{ number_format((float) $turno->valor_abertura, 2, ',', '.') }}</td></tr>
            <tr><th>Entradas (suprimento + vendas)</th><td class="text-end text-success">+ R$ {{ number_format($turno->totalEntradasMovimentos(), 2, ',', '.') }}</td></tr>
            <tr><th>Saídas (sangrias)</th><td class="text-end text-danger">− R$ {{ number_format($turno->totalSaidasMovimentos(), 2, ',', '.') }}</td></tr>
            <tr class="table-light"><th class="fw-bold">Saldo esperado</th><td class="text-end fw-bold">R$ {{ number_format($turno->saldoEsperado(), 2, ',', '.') }}</td></tr>
        </tbody>
    </table>

    <h2 class="h6 fw-bold mt-4 mb-2">Movimentações</h2>
    <table class="table table-sm">
        <thead><tr><th>Hora</th><th>Tipo</th><th>Descrição</th><th class="text-end">Valor</th></tr></thead>
        <tbody>
            @forelse ($turno->movimentos as $m)
                <tr>
                    <td>{{ $m->created_at->format('H:i') }}</td>
                    <td>{{ \App\Models\CaixaMovimento::rotuloTipo($m->tipo) }}</td>
                    <td>{{ $m->descricao }}</td>
                    <td class="text-end {{ $m->isEntrada() ? 'text-success' : 'text-danger' }}">
                        {{ $m->isEntrada() ? '+' : '−' }} R$ {{ number_format((float) $m->valor, 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">Nenhum lançamento.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection

@push('scripts')
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 250);
        });
    </script>
@endpush
