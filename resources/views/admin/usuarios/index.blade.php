@extends('layouts.admin')

@section('title', 'Usuários')

@section('content')
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

    <form action="{{ route('admin.usuarios.index') }}" method="get" class="vf-filter-bar mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-6 col-lg-4">
                <label class="form-label small text-muted mb-1" for="filtro-q">Buscar</label>
                <input type="search" class="form-control form-control-sm" id="filtro-q" name="q" value="{{ request('q') }}" placeholder="Nome ou e-mail…">
            </div>
            <div class="col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filtrar</button>
                <a href="{{ route('admin.usuarios.index') }}" class="btn btn-sm btn-outline-secondary">Limpar</a>
            </div>
        </div>
    </form>

    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('admin.usuarios.create') }}" class="btn btn-success btn-sm">
            <i class="bi bi-person-plus me-1"></i>Novo usuário
        </a>
    </div>

    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 vf-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Empresa</th>
                        <th>Função</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usuarios as $u)
                        <tr>
                            <td class="text-muted small">{{ $u->id }}</td>
                            <td>{{ $u->name }}</td>
                            <td>
                                {{ $u->email }}
                                @if ($u->acessaPainelMaster())
                                    <span class="badge text-bg-primary ms-1">Master</span>
                                @endif
                            </td>
                            <td>
                                @if ($u->empresa)
                                    {{ $u->empresa->nome }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td><span class="small">{{ $u->rotuloRoleEquipe() }}</span></td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.usuarios.edit', $u) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                @if ((int) $u->id !== (int) auth()->id())
                                    <form action="{{ route('admin.usuarios.destroy', $u) }}" method="post" class="d-inline" onsubmit="return confirm('Remover este usuário?');">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nenhum usuário encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($usuarios->hasPages())
        <div class="mt-3 d-flex justify-content-center">
            {{ $usuarios->links() }}
        </div>
    @endif
@endsection
