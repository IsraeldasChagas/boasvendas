@extends('layouts.empresa')

@section('title', 'Fechamento de mesa')

@section('content')
<p class="text-muted">Selecione a comanda para aplicar taxa, desconto e registrar pagamentos (divisão de conta).</p>
<div class="list-group">
    @forelse ($comandas as $c)
        <a href="{{ route('empresa.mesas.fechamento.show', $c) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
            <div>
                <strong>Mesa #{{ $c->mesa?->numero }}</strong>
                <span class="text-muted">· {{ $c->cliente_nome ?: 'Cliente não informado' }}</span>
                <div class="small">{{ $c->garcom?->name ?? 'Sem garçom' }} · {{ $c->status->label() }}</div>
            </div>
            <span class="badge bg-primary fs-6">R$ {{ number_format((float) $c->total, 2, ',', '.') }}</span>
        </a>
    @empty
        <div class="list-group-item text-muted">Nenhuma comanda aguardando fechamento.</div>
    @endforelse
</div>
@endsection
