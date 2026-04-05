@extends('layouts.auth')

@section('title', 'Cadastro empresa')

@section('content')
    <h2 class="h4 fw-bold mb-1">Criar empresa</h2>
    <p class="small text-muted mb-4">Formulário ilustrativo — sem persistência.</p>
    <form action="#" method="post" onsubmit="return false;">
        @csrf
        <div class="mb-3">
            <label class="form-label">Nome da empresa</label>
            <input type="text" class="form-control" placeholder="Ex.: Sabor da Rua" disabled>
        </div>
        <div class="mb-3">
            <label class="form-label">CNPJ / CPF</label>
            <input type="text" class="form-control" placeholder="00.000.000/0001-00" disabled>
        </div>
        <div class="mb-3">
            <label class="form-label">E-mail comercial</label>
            <input type="email" class="form-control" placeholder="contato@empresa.com" disabled>
        </div>
        <div class="mb-3">
            <label class="form-label">WhatsApp</label>
            <input type="text" class="form-control" placeholder="(11) 99999-9999" disabled>
        </div>
        <div class="mb-3">
            <label class="form-label">Senha</label>
            <input type="password" class="form-control" disabled>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="t" disabled>
            <label class="form-check-label small" for="t">Aceito os termos (demo)</label>
        </div>
        <div class="d-grid">
            <a href="{{ route('empresa.dashboard') }}" class="btn btn-primary">Continuar para o painel (mock)</a>
        </div>
    </form>
    <p class="small text-center text-muted mt-3 mb-0"><a href="{{ route('login') }}">Já tenho conta</a></p>
@endsection
