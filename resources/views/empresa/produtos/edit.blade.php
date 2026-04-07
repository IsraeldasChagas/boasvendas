@extends('layouts.empresa')

@section('title', 'Editar produto')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Produtos', 'url' => route('empresa.produtos.index')],
        ['label' => 'Editar #'.$produto->id, 'url' => route('empresa.produtos.edit', $produto)],
    ]])
    <div class="vf-card p-4">
        <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
            <div>
                <h2 class="h5 fw-bold mb-1">{{ $produto->nome }}</h2>
                <p class="small text-muted mb-0">Código interno: <code class="user-select-all">{{ $produto->sku }}</code></p>
            </div>
            <span class="vf-badge {{ $produto->ativo ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">{{ $produto->ativo ? 'Ativo' : 'Inativo' }}</span>
        </div>
        <form action="{{ route('empresa.produtos.update', $produto) }}" method="post">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="nome">Nome</label>
                    <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome', $produto->nome) }}" required>
                    @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="categoria_id">Categoria</label>
                    <select class="form-select @error('categoria_id') is-invalid @enderror" id="categoria_id" name="categoria_id">
                        <option value="">— Sem categoria —</option>
                        @foreach ($categorias as $cat)
                            <option value="{{ $cat->id }}" @selected(old('categoria_id', $produto->categoria_id) == $cat->id)>{{ $cat->nome }}</option>
                        @endforeach
                    </select>
                    @error('categoria_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text"><a href="{{ route('empresa.categorias.index') }}">Gerenciar categorias</a></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="preco">Preço (R$)</label>
                    <input type="number" class="form-control @error('preco') is-invalid @enderror" id="preco" name="preco" value="{{ old('preco', $produto->preco) }}" min="0" step="0.01" required>
                    @error('preco')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="estoque">Estoque</label>
                    <input type="number" class="form-control @error('estoque') is-invalid @enderror" id="estoque" name="estoque" value="{{ old('estoque', $produto->estoque) }}" min="0" required>
                    @error('estoque')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="descricao">Descrição</label>
                    <textarea class="form-control @error('descricao') is-invalid @enderror" id="descricao" name="descricao" rows="3">{{ old('descricao', $produto->descricao) }}</textarea>
                    @error('descricao')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="permite_adicionais" id="permite_adicionais" value="1" {{ old('permite_adicionais', $produto->permite_adicionais) ? 'checked' : '' }}>
                        <label class="form-check-label" for="permite_adicionais">Permitir adicionais / retirar ingredientes na loja</label>
                    </div>
                    <p class="small text-muted mb-2">Opções vinculadas (cadastro em <a href="{{ route('empresa.adicionais.index') }}">Adicionais</a>).</p>
                    <div class="border rounded p-3 bg-light mb-2" style="max-height: 12rem; overflow-y: auto;">
                        @php $sel = old('adicional_ids', $produto->adicionais->pluck('id')->all()); @endphp
                        @forelse ($adicionais as $ad)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="adicional_ids[]" id="ad_{{ $ad->id }}" value="{{ $ad->id }}"
                                    @checked(in_array($ad->id, $sel, true))>
                                <label class="form-check-label" for="ad_{{ $ad->id }}">
                                    {{ $ad->nome }}
                                    @if ($ad->tipo === \App\Models\Adicional::TIPO_RETIRAR)
                                        <span class="text-muted small">(retirar)</span>
                                    @else
                                        <span class="text-muted small">(+ R$ {{ number_format((float) $ad->preco, 2, ',', '.') }})</span>
                                    @endif
                                </label>
                            </div>
                        @empty
                            <span class="small text-muted">Nenhum adicional cadastrado.</span>
                        @endforelse
                    </div>
                    @error('adicional_ids')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="visivel_loja" id="visivel_loja" value="1" {{ old('visivel_loja', $produto->visivel_loja) ? 'checked' : '' }}>
                        <label class="form-check-label" for="visivel_loja">Visível na loja pública</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="ativo" id="ativo" value="1" {{ old('ativo', $produto->ativo) ? 'checked' : '' }}>
                        <label class="form-check-label" for="ativo">Ativo</label>
                    </div>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">Atualizar</button>
                    <a href="{{ route('empresa.produtos.index') }}" class="btn btn-outline-secondary">Voltar</a>
                </div>
            </div>
        </form>
        <hr class="my-4">
        <form action="{{ route('empresa.produtos.destroy', $produto) }}" method="post" onsubmit="return confirm('Excluir este produto?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm">Excluir produto</button>
        </form>
    </div>
@endsection
