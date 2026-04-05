@extends('layouts.admin')

@section('title', 'Módulos')

@section('content')
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <p class="text-muted small mb-0">Funcionalidades do produto: situação comercial e categoria (ex.: Core, Premium).</p>
        </div>
        <a href="{{ route('admin.modulos.create') }}" class="btn btn-success btn-sm flex-shrink-0">
            <i class="bi bi-plus-lg me-1"></i>Novo módulo
        </a>
    </div>

    @if ($modulos->isEmpty())
        <div class="vf-card p-5 text-center text-muted">
            <p class="mb-3">Nenhum módulo cadastrado.</p>
            <a href="{{ route('admin.modulos.create') }}" class="btn btn-primary btn-sm">Criar primeiro módulo</a>
        </div>
    @else
        <div class="vf-card p-0">
            <ul class="list-group list-group-flush">
                @foreach ($modulos as $modulo)
                    <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold">{{ $modulo->nome }}</div>
                            <div class="small text-muted">Categoria: {{ $modulo->categoria !== '' ? $modulo->categoria : '—' }}</div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            @switch($modulo->situacao)
                                @case('ativo')
                                    <span class="vf-badge bg-success-subtle text-success">Ativo</span>
                                    @break
                                @case('addon')
                                    <span class="vf-badge bg-primary-subtle text-primary">Add-on</span>
                                    @break
                                @case('roadmap')
                                    <span class="vf-badge bg-secondary-subtle text-secondary">Roadmap</span>
                                    @break
                                @default
                                    <span class="vf-badge bg-light text-dark">{{ $modulo->situacao }}</span>
                            @endswitch
                            <a href="{{ route('admin.modulos.edit', $modulo) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                            <form action="{{ route('admin.modulos.destroy', $modulo) }}" method="post" class="d-inline"
                                  onsubmit="return confirm('Remover este módulo?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection
