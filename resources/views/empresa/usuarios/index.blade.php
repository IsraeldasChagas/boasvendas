@extends('layouts.empresa')

@section('title', 'Usuários')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('empresa.dashboard')],
        ['label' => 'Usuários', 'url' => route('empresa.usuarios.index')],
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
        <h2 class="h5 fw-bold mb-0">Equipe — {{ $empresa->nome }}</h2>
        <a href="{{ route('empresa.usuarios.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-person-plus me-1"></i>Novo usuário</a>
    </div>

    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 vf-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Perfil</th>
                        <th>Situação</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usuarios as $u)
                        @php
                            $verif = $u->email_verified_at !== null;
                        @endphp
                        <tr>
                            <td class="fw-medium">
                                {{ $u->name }}
                                @if ((int) $u->id === (int) auth()->id())
                                    <span class="small text-muted">(você)</span>
                                @endif
                            </td>
                            <td class="small">{{ $u->email }}</td>
                            <td class="small">{{ $u->rotuloRoleEquipe() }}</td>
                            <td>
                                <span class="vf-badge {{ $verif ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                    {{ $verif ? 'Verificado' : 'Pendente' }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('empresa.usuarios.edit', ['usuario' => $u]) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                @if ((int) $u->id !== (int) auth()->id() && ! $u->acessaPainelMaster())
                                    <form action="{{ route('empresa.usuarios.destroy', ['usuario' => $u]) }}" method="post" class="d-inline" onsubmit="return confirm('Remover este usuário da empresa?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">Nenhum usuário nesta empresa.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($usuarios->hasPages())
            <div class="p-3 border-top">{{ $usuarios->links() }}</div>
        @endif
    </div>
@endsection
