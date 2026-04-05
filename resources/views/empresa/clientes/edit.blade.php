@extends('layouts.empresa')

@section('title', 'Editar cliente')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Clientes', 'url' => route('empresa.clientes.index')],
        ['label' => $cliente->nome, 'url' => route('empresa.clientes.edit', $cliente)],
    ]])

    <div class="vf-card p-4" style="max-width: 40rem;">
        <h2 class="h5 fw-bold mb-4">Editar cliente</h2>
        <form action="{{ route('empresa.clientes.update', $cliente) }}" method="post">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label" for="nome">Nome</label>
                <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome', $cliente->nome) }}" required>
                @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="telefone">Telefone</label>
                    <input type="text" class="form-control @error('telefone') is-invalid @enderror" id="telefone" name="telefone" value="{{ old('telefone', $cliente->telefone) }}">
                    @error('telefone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email">E-mail</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $cliente->email) }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="documento">CPF / documento</label>
                <input type="text" class="form-control @error('documento') is-invalid @enderror" id="documento" name="documento" value="{{ old('documento', $cliente->documento) }}">
                @error('documento')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="observacoes">Observações</label>
                <textarea class="form-control @error('observacoes') is-invalid @enderror" id="observacoes" name="observacoes" rows="3">{{ old('observacoes', $cliente->observacoes) }}</textarea>
                @error('observacoes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="ativo" id="ativo" value="1" {{ old('ativo', $cliente->ativo) ? 'checked' : '' }}>
                <label class="form-check-label" for="ativo">Cliente ativo</label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Atualizar</button>
                <a href="{{ route('empresa.clientes.index') }}" class="btn btn-outline-secondary">Voltar</a>
            </div>
        </form>
        <hr class="my-4">
        <form action="{{ route('empresa.clientes.destroy', $cliente) }}" method="post" onsubmit="return confirm('Excluir este cliente?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm">Excluir cliente</button>
        </form>
    </div>
@endsection
