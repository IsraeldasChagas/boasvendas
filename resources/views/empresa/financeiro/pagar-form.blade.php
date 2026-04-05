@extends('layouts.empresa')

@section('title', $titulo->exists ? 'Editar a pagar' : 'Nova conta a pagar')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Contas a pagar', 'url' => route('empresa.financeiro.contas-pagar')],
        ['label' => $titulo->exists ? 'Editar' : 'Novo', 'url' => '#'],
    ]])

    <div class="vf-card p-4" style="max-width: 36rem;">
        <h2 class="h5 fw-bold mb-4">{{ $titulo->exists ? 'Editar conta a pagar' : 'Nova conta a pagar' }}</h2>
        <form action="{{ $titulo->exists ? route('empresa.financeiro.contas-pagar.update', $titulo) : route('empresa.financeiro.contas-pagar.store') }}" method="post">
            @csrf
            @if ($titulo->exists)
                @method('PUT')
            @endif
            <div class="mb-3">
                <label class="form-label" for="contraparte">Fornecedor</label>
                <input type="text" class="form-control @error('contraparte') is-invalid @enderror" id="contraparte" name="contraparte" value="{{ old('contraparte', $titulo->contraparte) }}" placeholder="Nome ou referência">
                @error('contraparte')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="descricao">Descrição</label>
                <input type="text" class="form-control @error('descricao') is-invalid @enderror" id="descricao" name="descricao" value="{{ old('descricao', $titulo->descricao) }}" required>
                @error('descricao')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="valor">Valor (R$)</label>
                    <input type="number" step="0.01" min="0.01" class="form-control @error('valor') is-invalid @enderror" id="valor" name="valor" value="{{ old('valor', $titulo->valor) }}" required>
                    @error('valor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="vencimento">Vencimento</label>
                    <input type="date" class="form-control @error('vencimento') is-invalid @enderror" id="vencimento" name="vencimento" value="{{ old('vencimento', $titulo->vencimento?->format('Y-m-d')) }}" required>
                    @error('vencimento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label" for="observacoes">Observações</label>
                <textarea class="form-control @error('observacoes') is-invalid @enderror" id="observacoes" name="observacoes" rows="2">{{ old('observacoes', $titulo->observacoes) }}</textarea>
                @error('observacoes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="{{ route('empresa.financeiro.contas-pagar') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
