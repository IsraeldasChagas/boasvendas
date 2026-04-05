@extends('layouts.empresa')

@section('title', 'Editar categoria')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Categorias', 'url' => route('empresa.categorias.index')],
        ['label' => $categoria->nome, 'url' => route('empresa.categorias.edit', $categoria)],
    ]])

    <div class="vf-card p-4" style="max-width: 32rem;">
        <h2 class="h5 fw-bold mb-4">Editar categoria</h2>
        <form action="{{ route('empresa.categorias.update', $categoria) }}" method="post">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label" for="nome">Nome</label>
                <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome', $categoria->nome) }}" required>
                @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="ordem">Ordem na listagem</label>
                <input type="number" class="form-control @error('ordem') is-invalid @enderror" id="ordem" name="ordem" value="{{ old('ordem', $categoria->ordem) }}" min="0">
                @error('ordem')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="ativo" id="ativo" value="1" {{ old('ativo', $categoria->ativo) ? 'checked' : '' }}>
                <label class="form-check-label" for="ativo">Categoria ativa</label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Atualizar</button>
                <a href="{{ route('empresa.categorias.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
