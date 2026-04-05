@extends('layouts.empresa')

@section('title', 'Pedidos')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('empresa.dashboard')],
        ['label' => 'Pedidos', 'url' => route('empresa.pedidos.index')],
    ]])

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 fw-bold mb-0">Pedidos — {{ $empresa->nome }}</h2>
    </div>

    <form action="{{ route('empresa.pedidos.index') }}" method="get" class="vf-filter-bar mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4 col-lg-3">
                <label class="form-label small text-muted mb-1" for="pd-st">Status</label>
                <select class="form-select form-select-sm" id="pd-st" name="status" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    @foreach (\App\Models\Pedido::statusRotulos() as $val => $rotulo)
                        <option value="{{ $val }}" @selected(request('status') === $val)>{{ $rotulo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <a href="{{ route('empresa.pedidos.index') }}" class="btn btn-sm btn-outline-secondary">Limpar</a>
            </div>
        </div>
    </form>

    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 vf-table">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Canal</th>
                        <th>Horário</th>
                        <th>Status</th>
                        <th class="text-end">Valor</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pedidos as $p)
                        <tr>
                            <td class="fw-semibold">{{ $p->codigo_publico }}</td>
                            <td class="small">{{ $p->cliente_nome }}</td>
                            <td><span class="vf-badge bg-light text-secondary border">{{ $p->canal === \App\Models\Pedido::CANAL_LOJA ? 'Loja' : $p->canal }}</span></td>
                            <td class="small text-muted">{{ $p->created_at->format('d/m H:i') }}</td>
                            <td><span class="vf-badge {{ $p->classeBadgeStatus() }}">{{ $p->rotuloStatus() }}</span></td>
                            <td class="text-end fw-medium">R$ {{ number_format((float) $p->total, 2, ',', '.') }}</td>
                            <td class="text-end"><a href="{{ route('empresa.pedidos.show', $p) }}" class="btn btn-sm btn-outline-primary">Abrir</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">Nenhum pedido ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($pedidos->hasPages())
            <div class="p-3 border-top">{{ $pedidos->links() }}</div>
        @endif
    </div>
@endsection
