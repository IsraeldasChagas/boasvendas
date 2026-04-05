@extends('layouts.admin')

@section('title', 'Novo módulo')

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.modulos.index') }}" class="small text-decoration-none">&larr; Voltar aos módulos</a>
    </div>
    <h2 class="h5 fw-bold mb-3">Novo módulo</h2>

    <div class="vf-card p-4" style="max-width: 36rem;">
        <form action="{{ route('admin.modulos.store') }}" method="post">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="nome">Nome do módulo</label>
                <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome') }}" required>
                @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="categoria">Categoria</label>
                <input type="text" class="form-control @error('categoria') is-invalid @enderror" id="categoria" name="categoria" value="{{ old('categoria') }}" placeholder="Ex.: Core, Premium (deixe vazio para —)">
                @error('categoria')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="situacao">Situação</label>
                <select class="form-select @error('situacao') is-invalid @enderror" id="situacao" name="situacao" required>
                    @foreach (\App\Models\Modulo::situacoes() as $valor => $rotulo)
                        <option value="{{ $valor }}" @selected(old('situacao') === $valor)>{{ $rotulo }}</option>
                    @endforeach
                </select>
                @error('situacao')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label class="form-label" for="ordem">Ordem na listagem</label>
                <input type="number" class="form-control @error('ordem') is-invalid @enderror" id="ordem" name="ordem" value="{{ old('ordem', 0) }}" min="0">
                @error('ordem')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="{{ route('admin.modulos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
