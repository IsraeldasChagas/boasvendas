@extends('layouts.empresa')

@section('title', $despesa->exists ? 'Editar despesa fixa' : 'Nova despesa fixa')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Despesas fixas', 'url' => route('empresa.financeiro.despesas-fixas.index')],
        ['label' => $despesa->exists ? 'Editar' : 'Nova', 'url' => '#'],
    ]])

    <div class="vf-card p-4" style="max-width: 36rem;">
        <h2 class="h5 fw-bold mb-4">{{ $despesa->exists ? 'Editar despesa fixa' : 'Nova despesa fixa' }}</h2>
        <form action="{{ $despesa->exists ? route('empresa.financeiro.despesas-fixas.update', $despesa) : route('empresa.financeiro.despesas-fixas.store') }}" method="post">
            @csrf
            @if ($despesa->exists)
                @method('PUT')
            @endif
            <div class="mb-3">
                <label class="form-label" for="nome">Nome da despesa</label>
                <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome', $despesa->nome) }}" required maxlength="255" placeholder="Ex.: Aluguel, Internet, Gás de cozinha">
                @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="valor_mensal">Valor mensal (R$)</label>
                <input type="number" step="0.01" min="0" class="form-control @error('valor_mensal') is-invalid @enderror" id="valor_mensal" name="valor_mensal" value="{{ old('valor_mensal', $despesa->valor_mensal) }}" required>
                @error('valor_mensal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Valor estimado ou contratual por mês (pode ser 0 se quiser só registrar o nome).</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="categoria">Categoria <span class="text-muted fw-normal">(opcional)</span></label>
                <input type="text" class="form-control @error('categoria') is-invalid @enderror" id="categoria" name="categoria" value="{{ old('categoria', $despesa->categoria) }}" maxlength="120" placeholder="Ex.: Estrutura, Cozinha, TI">
                @error('categoria')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="observacoes">Observações</label>
                <textarea class="form-control @error('observacoes') is-invalid @enderror" id="observacoes" name="observacoes" rows="2" maxlength="5000" placeholder="Notas, vencimento, conta bancária…">{{ old('observacoes', $despesa->observacoes) }}</textarea>
                @error('observacoes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" id="ativo" name="ativo" value="1" @checked(old('ativo', $despesa->ativo ?? true))>
                <label class="form-check-label" for="ativo">Ativa (entra no total mensal)</label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="{{ route('empresa.financeiro.despesas-fixas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
