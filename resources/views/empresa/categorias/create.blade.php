@extends('layouts.empresa')

@section('title', 'Nova categoria')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Categorias', 'url' => route('empresa.categorias.index')],
        ['label' => 'Nova', 'url' => route('empresa.categorias.create')],
    ]])

    <div class="vf-card p-4" style="max-width: 32rem;">
        <h2 class="h5 fw-bold mb-4">Nova categoria</h2>
        <form action="{{ route('empresa.categorias.store') }}" method="post">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="nome">Nome</label>
                <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome') }}" required placeholder="Ex.: Lanches">
                @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="ordem">Ordem na listagem</label>
                <input type="number" class="form-control @error('ordem') is-invalid @enderror" id="ordem" name="ordem" value="{{ old('ordem', 0) }}" min="0">
                @error('ordem')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="ativo" id="ativo" value="1" {{ old('ativo', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="ativo">Categoria ativa</label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="{{ route('empresa.categorias.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
