@extends('layouts.empresa')

@section('title', 'Estoque')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Estoque', 'url' => route('empresa.estoque.index')],
    ]])

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="h4 fw-bold mb-1">Estoque</h2>
            <p class="small text-muted mb-0">Saldo por produto. As vendas baixam automaticamente (PDV, loja, mesas e venda externa).</p>
        </div>
        @if ($baixoCount > 0)
            <a href="{{ route('empresa.estoque.index', ['filtro' => 'baixo']) }}" class="btn btn-warning btn-sm">
                <i class="bi bi-exclamation-triangle me-1"></i>{{ $baixoCount }} com estoque baixo
            </a>
        @endif
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form action="{{ route('empresa.estoque.index') }}" method="get" class="vf-filter-bar mb-3 d-flex flex-wrap gap-2">
        <input type="search" class="form-control form-control-sm" style="max-width: 20rem" name="q" value="{{ request('q') }}" placeholder="Buscar por nome ou código…">
        @if (request('filtro') === 'baixo')
            <input type="hidden" name="filtro" value="baixo">
        @endif
        <button type="submit" class="btn btn-sm btn-primary">Buscar</button>
        @if (request()->hasAny(['q', 'filtro']))
            <a href="{{ route('empresa.estoque.index') }}" class="btn btn-sm btn-outline-secondary">Limpar</a>
        @endif
    </form>

    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 vf-table align-middle">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Categoria</th>
                        <th class="text-end">Saldo</th>
                        <th>Situação</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($produtos as $p)
                        <tr>
                            <td>
                                <div class="small fw-medium">{{ $p->nome }}</div>
                                <div class="text-muted" style="font-size:.75rem">{{ $p->sku ?: '—' }}</div>
                            </td>
                            <td class="small">{{ $p->categoria?->nome ?? '—' }}</td>
                            <td class="text-end fw-semibold {{ $p->controlaEstoque() && $p->estoque <= 0 ? 'text-danger' : ($p->controlaEstoque() && $p->estoque <= $limiarBaixo ? 'text-warning' : '') }}">
                                {{ $p->controlaEstoque() ? $p->estoque : '∞' }}
                            </td>
                            <td>
                                @if (! $p->controlaEstoque())
                                    <span class="vf-badge bg-secondary-subtle text-secondary">Sem controle</span>
                                @elseif ($p->estoque <= 0)
                                    <span class="vf-badge bg-danger-subtle text-danger">Zerado</span>
                                @elseif ($p->estoque <= $limiarBaixo)
                                    <span class="vf-badge bg-warning-subtle text-warning-emphasis">Baixo</span>
                                @else
                                    <span class="vf-badge bg-success-subtle text-success">OK</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('empresa.estoque.produto', $p) }}" class="btn btn-sm btn-outline-primary">Movimentar</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Nenhum produto encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
