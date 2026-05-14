@extends('layouts.empresa')

@section('title', 'Comandas abertas')

@section('content')
<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>Mesa</th>
                <th>Cliente</th>
                <th>Garçom</th>
                <th>Status</th>
                <th>Aberta em</th>
                <th class="text-end">Total</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($comandas as $c)
                <tr>
                    <td>#{{ $c->mesa?->numero }} @if($c->mesa?->nome)<span class="text-muted">· {{ $c->mesa->nome }}</span>@endif</td>
                    <td>{{ $c->cliente_nome ?: '—' }}</td>
                    <td>{{ $c->garcom?->name ?? '—' }}</td>
                    <td><span class="badge bg-secondary">{{ $c->status->label() }}</span></td>
                    <td>{{ $c->aberta_em?->format('d/m/Y H:i') }}</td>
                    <td class="text-end">R$ {{ number_format((float) $c->total, 2, ',', '.') }}</td>
                    <td class="text-end">
                        <a href="{{ route('empresa.comandas.show', $c) }}" class="btn btn-sm btn-primary">Abrir</a>
                        @if (auth()->user()->role !== \App\Models\User::ROLE_ATENDENTE)
                            <a href="{{ route('empresa.mesas.fechamento.show', $c) }}" class="btn btn-sm btn-outline-success">Fechamento</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Nenhuma comanda aberta.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
