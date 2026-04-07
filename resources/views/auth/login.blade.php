@extends('layouts.auth')

@section('title', 'Entrar')

@section('content')
    <h2 class="h4 fw-bold mb-3">Entrar</h2>
    <p class="small text-muted mb-4">Use <strong>master@vendaffacil.com.br</strong> para o painel <span class="text-nowrap">/admin</span> (master) e <strong>empresa@vendaffacil.com.br</strong> para o painel da empresa.</p>
    <form action="{{ route('login.authenticate') }}" method="post">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="email">E-mail</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Senha</label>
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required autocomplete="current-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label small" for="remember">Lembrar</label>
            </div>
            <a href="{{ route('auth.esqueci-senha') }}" class="small">Esqueci a senha</a>
        </div>
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">Entrar</button>
        </div>
    </form>
    <hr class="my-4">
    <p class="small text-center text-muted mb-0">Não tem conta? <a href="{{ route('auth.cadastro-empresa') }}">Cadastre sua empresa</a></p>
@endsection
