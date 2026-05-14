@extends('layouts.empresa')

@section('title', 'Fiscal — Notas')

@section('content')
    @php
        $titulo = match ($aba) {
            'emitidas' => 'Notas emitidas',
            'pendentes' => 'Notas pendentes',
            'rejeicoes' => 'Rejeições',
            default => 'Notas',
        };
    @endphp
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Fiscal', 'url' => route('empresa.fiscal.dashboard')],
        ['label' => $titulo, 'url' => url()->current()],
    ]])

    <h2 class="h4 fw-bold mb-3">{{ $titulo }}</h2>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('empresa.fiscal.notas.emitidas') }}" class="btn btn-sm {{ $aba === 'emitidas' ? 'btn-primary' : 'btn-outline-secondary' }}">Emitidas</a>
        <a href="{{ route('empresa.fiscal.notas.pendentes') }}" class="btn btn-sm {{ $aba === 'pendentes' ? 'btn-primary' : 'btn-outline-secondary' }}">Pendentes</a>
        <a href="{{ route('empresa.fiscal.notas.rejeicoes') }}" class="btn btn-sm {{ $aba === 'rejeicoes' ? 'btn-primary' : 'btn-outline-secondary' }}">Rejeições</a>
    </div>

    <form action="{{ url()->current() }}" method="get" class="vf-filter-bar mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label small text-muted mb-1" for="q">Buscar (pedido, chave, número)</label>
                <input type="search" class="form-control form-control-sm" id="q" name="q" value="{{ request('q') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-secondary btn-sm">Filtrar</button>
            </div>
        </div>
    </form>

    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 vf-table align-middle small">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Valor</th>
                        <th>Atualizado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($notas as $n)
                        <tr>
                            <td>
                                @if ($n->pedido)
                                    <a href="{{ route('empresa.pedidos.show', $n->pedido) }}">{{ $n->pedido->codigo_publico }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td><code>{{ $n->tipo_nota?->value ?? $n->tipo_nota }}</code></td>
                            <td><span class="vf-badge {{ $n->status->classeBadge() }}">{{ $n->status->rotulo() }}</span></td>
                            <td>@if ($n->valor_total !== null) R$ {{ number_format((float) $n->valor_total, 2, ',', '.') }} @else — @endif</td>
                            <td class="text-nowrap">{{ $n->updated_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Nenhum registro.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $notas->links() }}</div>
@endsection
