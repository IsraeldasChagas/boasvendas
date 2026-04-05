@extends('layouts.auth')

@section('title', 'Cadastro usuário')

@section('content')
    <h2 class="h4 fw-bold mb-1">Convidar usuário</h2>
    <p class="small text-muted mb-4">Cadastre colaboradores no painel da empresa após entrar com uma conta vinculada à loja.</p>
    <div class="vf-card p-4 mb-3 border">
        <p class="small mb-3">Faça login com um usuário da sua empresa e use <strong>Usuários → Novo usuário</strong> para criar acesso com nome, e-mail, perfil e senha.</p>
        <div class="d-grid gap-2">
            <a href="{{ route('login') }}" class="btn btn-primary">Entrar</a>
            <a href="{{ route('empresa.usuarios.index') }}" class="btn btn-outline-secondary">Ir para usuários (requer login)</a>
        </div>
    </div>
@endsection
