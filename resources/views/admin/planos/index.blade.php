@extends('layouts.admin')

@section('title', 'Planos SaaS')

@section('content')
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h2 class="h5 fw-bold mb-0">Planos comerciais</h2>
        <a href="{{ route('admin.planos.create') }}" class="btn btn-success btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Novo plano
        </a>
    </div>

    @if ($planos->isEmpty())
        <div class="vf-card p-5 text-center text-muted">
            <p class="mb-3">Nenhum plano cadastrado.</p>
            <a href="{{ route('admin.planos.create') }}" class="btn btn-primary btn-sm">Criar primeiro plano</a>
        </div>
    @else
        <div class="row g-3">
            @foreach ($planos as $plano)
                <div class="col-lg-4">
                    <div class="vf-card p-4 h-100 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                            <h3 class="h5 fw-bold mb-0">{{ $plano->nome }}</h3>
                            @if (! $plano->ativo)
                                <span class="vf-badge bg-secondary-subtle text-secondary">Inativo</span>
                            @endif
                        </div>
                        <p class="h3 text-primary mb-2">
                            R$ {{ number_format((float) $plano->preco_mensal, 2, ',', '.') }}
                            <small class="fs-6 text-muted">/mês</small>
                        </p>
                        <ul class="small text-muted mb-3 flex-grow-1">
                            <li>{{ $plano->feature_primaria }}</li>
                            <li>{{ $plano->feature_secundaria }}</li>
                        </ul>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.planos.edit', $plano) }}" class="btn btn-outline-primary btn-sm flex-grow-1">Editar</a>
                            <form action="{{ route('admin.planos.destroy', $plano) }}" method="post" class="d-inline"
                                  onsubmit="return confirm('Remover este plano?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm">Excluir</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
