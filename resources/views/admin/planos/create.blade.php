@extends('layouts.admin')

@section('title', 'Novo plano')

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.planos.index') }}" class="small text-decoration-none">&larr; Voltar aos planos</a>
    </div>
    <h2 class="h5 fw-bold mb-3">Novo plano</h2>

    <div class="vf-card p-4" style="max-width: 36rem;">
        <form action="{{ route('admin.planos.store') }}" method="post">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="nome">Nome do plano</label>
                <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome') }}" required>
                @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="preco_mensal">Preço mensal (R$)</label>
                <input type="number" class="form-control @error('preco_mensal') is-invalid @enderror" id="preco_mensal" name="preco_mensal" value="{{ old('preco_mensal') }}" min="0" step="0.01" required>
                @error('preco_mensal')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="feature_primaria">Destaque 1 (lista)</label>
                <input type="text" class="form-control @error('feature_primaria') is-invalid @enderror" id="feature_primaria" name="feature_primaria" value="{{ old('feature_primaria') }}" required>
                @error('feature_primaria')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="feature_secundaria">Destaque 2 (lista)</label>
                <input type="text" class="form-control @error('feature_secundaria') is-invalid @enderror" id="feature_secundaria" name="feature_secundaria" value="{{ old('feature_secundaria') }}" required>
                @error('feature_secundaria')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="ordem">Ordem na listagem</label>
                <input type="number" class="form-control @error('ordem') is-invalid @enderror" id="ordem" name="ordem" value="{{ old('ordem', 0) }}" min="0">
                @error('ordem')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="ativo" id="ativo" value="1" {{ old('ativo', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="ativo">Plano ativo (visível para uso)</label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="{{ route('admin.planos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
