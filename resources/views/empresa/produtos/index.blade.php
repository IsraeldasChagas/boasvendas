@extends('layouts.empresa')

@section('title', 'Produtos')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('empresa.dashboard')],
        ['label' => 'Produtos', 'url' => route('empresa.produtos.index')],
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
        <h2 class="h5 fw-bold mb-0">Catálogo</h2>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('empresa.categorias.index') }}" class="btn btn-outline-secondary btn-sm">Categorias</a>
            <a href="{{ route('empresa.produtos.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Novo produto</a>
        </div>
    </div>

    <form action="{{ route('empresa.produtos.index') }}" method="get" class="vf-filter-bar mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4 col-lg-3">
                <label class="form-label small text-muted mb-1" for="filtro-q">Buscar</label>
                <input type="search" class="form-control form-control-sm" id="filtro-q" name="q" value="{{ request('q') }}" placeholder="Nome, código interno, categoria…">
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
                <a href="{{ route('empresa.produtos.index') }}" class="btn btn-sm btn-outline-secondary">Limpar</a>
            </div>
        </div>
    </form>

    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 vf-table">
                <thead><tr><th style="width:3.5rem;"></th><th>Cód. interno</th><th>Nome</th><th>Categoria</th><th>Preço</th><th>Estoque</th><th>Status</th><th class="text-end">Ações</th></tr></thead>
                <tbody>
                    @forelse ($produtos as $pr)
                        <tr>
                            <td class="p-1">
                                @if ($pr->urlFoto())
                                    <img src="{{ $pr->urlFoto() }}" alt="" width="40" height="40" class="rounded border object-fit-cover" style="width:40px;height:40px;">
                                @else
                                    <span class="d-inline-flex align-items-center justify-content-center rounded border bg-light text-muted" style="width:40px;height:40px;"><i class="bi bi-image small"></i></span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $pr->sku }}</td>
                            <td class="fw-medium">{{ $pr->nome }}</td>
                            <td>{{ $pr->categoria?->nome ?? '—' }}</td>
                            <td>R$ {{ number_format((float) $pr->preco, 2, ',', '.') }}</td>
                            <td>{{ $pr->estoque }}</td>
                            <td>
                                <span class="vf-badge {{ $pr->ativo ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">{{ $pr->ativo ? 'Ativo' : 'Inativo' }}</span>
                                @if (! $pr->visivel_loja)
                                    <span class="vf-badge bg-secondary-subtle text-secondary ms-1">Oculto</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('empresa.produtos.edit', $pr) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                Nenhum produto cadastrado.
                                <a href="{{ route('empresa.produtos.create') }}">Criar primeiro</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
