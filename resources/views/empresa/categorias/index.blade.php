@extends('layouts.empresa')

@section('title', 'Categorias')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('empresa.dashboard')],
        ['label' => 'Categorias', 'url' => route('empresa.categorias.index')],
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

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h2 class="h5 fw-bold mb-0">Categorias do cardápio</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('empresa.produtos.index') }}" class="btn btn-outline-secondary btn-sm">Produtos</a>
            <a href="{{ route('empresa.categorias.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nova categoria</a>
        </div>
    </div>

    <div class="vf-card p-0">
        <ul class="list-group list-group-flush">
            @forelse ($categorias as $c)
                <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <span class="fw-medium">{{ $c->nome }}</span>
                        <span class="small text-muted ms-2">{{ $c->produtos_count }} {{ $c->produtos_count === 1 ? 'produto' : 'produtos' }}</span>
                        @if (! $c->ativo)
                            <span class="vf-badge bg-secondary-subtle text-secondary ms-1">Inativa</span>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="small text-muted" title="Ordem de exibição">#{{ $c->ordem }}</span>
                        <a href="{{ route('empresa.categorias.edit', $c) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                        <form action="{{ route('empresa.categorias.destroy', $c) }}" method="post" class="d-inline"
                              onsubmit="return confirm('Excluir esta categoria?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                        </form>
                    </div>
                </li>
            @empty
                <li class="list-group-item text-center text-muted py-5">
                    Nenhuma categoria. <a href="{{ route('empresa.categorias.create') }}">Criar primeira</a>
                </li>
            @endforelse
        </ul>
    </div>
@endsection
