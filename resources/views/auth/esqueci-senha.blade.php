@extends('layouts.auth')

@section('title', 'Recuperar senha')

@section('content')
    <h2 class="h4 fw-bold mb-1">Esqueci a senha</h2>
    <p class="small text-muted mb-4">Simulação — nenhum e-mail será enviado.</p>
    <form action="#" method="post" onsubmit="return false;">
        @csrf
        <div class="mb-3">
            <label class="form-label">E-mail da conta</label>
            <input type="email" class="form-control" placeholder="voce@email.com" disabled>
        </div>
        <div class="d-grid gap-2">
            <a href="{{ route('auth.redefinir-senha') }}" class="btn btn-primary">Ir para redefinição (mock)</a>
            <a href="{{ route('login') }}" class="btn btn-outline-secondary">Voltar ao login</a>
        </div>
    </form>
@endsection
