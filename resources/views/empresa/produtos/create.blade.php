@extends('layouts.empresa')

@section('title', 'Novo produto')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Produtos', 'url' => route('empresa.produtos.index')],
        ['label' => 'Novo', 'url' => route('empresa.produtos.create')],
    ]])
    <div class="row">
        <div class="col-lg-8">
            <div class="vf-card p-4">
                <h2 class="h5 fw-bold mb-4">Dados do produto</h2>
                <form action="{{ route('empresa.produtos.store') }}" method="post">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label" for="nome">Nome</label>
                            <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome') }}" placeholder="Ex.: X-Burger" required>
                            @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="sku">SKU</label>
                            <input type="text" class="form-control @error('sku') is-invalid @enderror" id="sku" name="sku" value="{{ old('sku') }}" placeholder="Ex.: SKU-001" required>
                            @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Único dentro da sua empresa.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="categoria_id">Categoria</label>
                            <select class="form-select @error('categoria_id') is-invalid @enderror" id="categoria_id" name="categoria_id">
                                <option value="">— Sem categoria —</option>
                                @foreach ($categorias as $cat)
                                    <option value="{{ $cat->id }}" @selected(old('categoria_id') == $cat->id)>{{ $cat->nome }}</option>
                                @endforeach
                            </select>
                            @error('categoria_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text"><a href="{{ route('empresa.categorias.create') }}">Nova categoria</a></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="preco">Preço (R$)</label>
                            <input type="number" class="form-control @error('preco') is-invalid @enderror" id="preco" name="preco" value="{{ old('preco') }}" min="0" step="0.01" required>
                            @error('preco')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="estoque">Estoque</label>
                            <input type="number" class="form-control @error('estoque') is-invalid @enderror" id="estoque" name="estoque" value="{{ old('estoque', 0) }}" min="0" required>
                            @error('estoque')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="descricao">Descrição</label>
                            <textarea class="form-control @error('descricao') is-invalid @enderror" id="descricao" name="descricao" rows="3">{{ old('descricao') }}</textarea>
                            @error('descricao')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="visivel_loja" id="visivel_loja" value="1" {{ old('visivel_loja', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="visivel_loja">Visível na loja pública</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="ativo" id="ativo" value="1" {{ old('ativo', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="ativo">Ativo (disponível para venda)</label>
                            </div>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Salvar</button>
                            <a href="{{ route('empresa.produtos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
