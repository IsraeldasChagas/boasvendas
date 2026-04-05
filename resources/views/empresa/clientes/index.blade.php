@extends('layouts.empresa')

@section('title', 'Clientes')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('empresa.dashboard')],
        ['label' => 'Clientes', 'url' => route('empresa.clientes.index')],
    ]])

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 fw-bold mb-0">Clientes</h2>
        <a href="{{ route('empresa.clientes.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Novo cliente</a>
    </div>

    <form action="{{ route('empresa.clientes.index') }}" method="get" class="vf-filter-bar mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4 col-lg-3">
                <label class="form-label small text-muted mb-1" for="filtro-q">Buscar</label>
                <input type="search" class="form-control form-control-sm" id="filtro-q" name="q" value="{{ request('q') }}" placeholder="Nome, e-mail, telefone…">
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small text-muted mb-1" for="filtro-ativo">Status</label>
                <select class="form-select form-select-sm" id="filtro-ativo" name="ativo">
                    <option value="">Todos</option>
                    <option value="1" @selected(request('ativo') === '1')>Ativo</option>
                    <option value="0" @selected(request('ativo') === '0')>Inativo</option>
                </select>
            </div>
            <div class="col-md-2 col-lg-auto ms-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filtrar</button>
                <a href="{{ route('empresa.clientes.index') }}" class="btn btn-sm btn-outline-secondary">Limpar</a>
            </div>
        </div>
    </form>

    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 vf-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Contato</th>
                        <th>Pedidos</th>
                        <th>Último pedido</th>
                        <th class="text-end">LTV</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clientes as $c)
                        <tr>
                            <td class="fw-medium">
                                {{ $c->nome }}
                                @if (! $c->ativo)
                                    <span class="vf-badge bg-secondary-subtle text-secondary ms-1">Inativo</span>
                                @endif
                            </td>
                            <td class="small">{{ $c->rotuloContato() }}</td>
                            <td class="small text-muted">—</td>
                            <td class="small text-muted">—</td>
                            <td class="text-end small text-muted">—</td>
                            <td class="text-end">
                                <a href="{{ route('empresa.clientes.edit', $c) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                Nenhum cliente cadastrado.
                                <a href="{{ route('empresa.clientes.create') }}">Adicionar</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <p class="small text-muted mt-2 mb-0">Pedidos, última compra e LTV aparecem quando o módulo de pedidos estiver integrado.</p>
@endsection
