@extends('layouts.empresa')

@section('title', 'Adicionais / ingredientes')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('empresa.dashboard')],
        ['label' => 'Adicionais', 'url' => route('empresa.adicionais.index')],
    ]])

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h2 class="h5 fw-bold mb-0">Adicionais e retiradas</h2>
            <p class="small text-muted mb-0">Cadastre opções para o cliente acrescentar (com preço) ou retirar ingredientes. Depois vincule aos produtos que permitem personalização.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('empresa.produtos.index') }}" class="btn btn-outline-secondary btn-sm">Produtos</a>
            <a href="{{ route('empresa.adicionais.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Novo adicional</a>
        </div>
    </div>

    <div class="vf-card p-0">
        <ul class="list-group list-group-flush">
            @forelse ($adicionais as $a)
                <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <span class="fw-medium">{{ $a->nome }}</span>
                        @if ($a->tipo === \App\Models\Adicional::TIPO_RETIRAR)
                            <span class="vf-badge bg-warning-subtle text-warning-emphasis ms-1">Retirar</span>
                        @else
                            <span class="vf-badge bg-success-subtle text-success ms-1">+ R$ {{ number_format((float) $a->preco, 2, ',', '.') }}</span>
                        @endif
                        <span class="small text-muted ms-2">{{ $a->produtos_count }} {{ $a->produtos_count === 1 ? 'produto' : 'produtos' }}</span>
                        @if (! $a->ativo)
                            <span class="vf-badge bg-secondary-subtle text-secondary ms-1">Inativo</span>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="small text-muted">#{{ $a->ordem }}</span>
                        <a href="{{ route('empresa.adicionais.edit', $a) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                        <form action="{{ route('empresa.adicionais.destroy', $a) }}" method="post" class="d-inline"
                              onsubmit="return confirm('Excluir este adicional? Será removido dos produtos vinculados.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                        </form>
                    </div>
                </li>
            @empty
                <li class="list-group-item text-center text-muted py-5">
                    Nenhum adicional. <a href="{{ route('empresa.adicionais.create') }}">Criar o primeiro</a>
                </li>
            @endforelse
        </ul>
    </div>
@endsection
