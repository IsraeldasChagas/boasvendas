@extends('layouts.auth')

@section('title', 'Nova senha')

@section('content')
    <h2 class="h4 fw-bold mb-1">Definir nova senha</h2>
    <p class="small text-muted mb-4">Token fictício na URL em produção.</p>
    <form action="#" method="post" onsubmit="return false;">
        @csrf
        <div class="mb-3">
            <label class="form-label">Nova senha</label>
            <input type="password" class="form-control" disabled>
        </div>
        <div class="mb-3">
            <label class="form-label">Confirmar senha</label>
            <input type="password" class="form-control" disabled>
        </div>
        <div class="d-grid">
            <a href="{{ route('login') }}" class="btn btn-primary">Salvar e entrar (mock)</a>
        </div>
    </form>
@endsection
