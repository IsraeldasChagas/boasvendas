@extends('layouts.empresa')

@section('title', 'Novo cliente')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Clientes', 'url' => route('empresa.clientes.index')],
        ['label' => 'Novo', 'url' => route('empresa.clientes.create')],
    ]])

    <div class="vf-card p-4" style="max-width: 40rem;">
        <h2 class="h5 fw-bold mb-4">Novo cliente</h2>
        <form action="{{ route('empresa.clientes.store') }}" method="post">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="nome">Nome</label>
                <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome') }}" required>
                @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="telefone">Telefone</label>
                    <input type="text" class="form-control @error('telefone') is-invalid @enderror" id="telefone" name="telefone" value="{{ old('telefone') }}" placeholder="(11) 98888-7777">
                    @error('telefone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email">E-mail</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="documento">CPF / documento</label>
                <input type="text" class="form-control @error('documento') is-invalid @enderror" id="documento" name="documento" value="{{ old('documento') }}">
                @error('documento')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="observacoes">Observações</label>
                <textarea class="form-control @error('observacoes') is-invalid @enderror" id="observacoes" name="observacoes" rows="3">{{ old('observacoes') }}</textarea>
                @error('observacoes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="ativo" id="ativo" value="1" {{ old('ativo', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="ativo">Cliente ativo</label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="{{ route('empresa.clientes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
