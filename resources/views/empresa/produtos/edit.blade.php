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
        <form action="{{ route('empresa.produtos.update', $produto) }}" method="post" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @php
                $uiRetirarIng = null;
                if (\Illuminate\Support\Facades\Schema::hasColumn('produtos', 'ingredientes_retirar_ui')) {
                    $uiRetirarIng = old('ingredientes_retirar_ui', $produto->ingredientes_retirar_ui ?? \App\Models\Produto::ING_RETIRAR_UI_STEPPER);
                }
            @endphp
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="foto">Foto do produto</label>
                    <div id="foto-preview-wrap" class="mb-2 {{ $produto->urlFoto() ? '' : 'd-none' }}">
                        <span class="small text-muted d-block mb-1" id="foto-preview-caption">{{ $produto->urlFoto() ? 'Foto atual' : 'Prévia' }}</span>
                        <img id="foto-preview" @if($produto->urlFoto()) src="{{ $produto->urlFoto() }}" @endif alt="Foto do produto" class="rounded border {{ $produto->urlFoto() ? '' : 'd-none' }}" width="160" height="160" style="max-height: 160px; width: auto; object-fit: cover;">
                    </div>
                    <input type="file" class="form-control @error('foto') is-invalid @enderror" id="foto" name="foto" accept="image/jpeg,image/png,image/webp,image/gif">
                    @error('foto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Opcional. Envie uma nova imagem para trocar. JPG, PNG, WebP ou GIF, até 3&nbsp;MB. Se não aparecer no site, confira <code>storage:link</code> no servidor.</div>
                </div>
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
                        @if ($uiRetirarIng !== null)
                            @include('partials.empresa.produto-ingredientes-retirar-ui', [
                                'valorUi' => $uiRetirarIng,
                            ])
                        @endif
                        @php
                            if (old('ingrediente_nomes') !== null) {
                                $oldNomes = old('ingrediente_nomes', []);
                                $oldAtuais = old('ingrediente_foto_atual', []);
                                $oldIds = old('ingrediente_ids', []);
                                if (! is_array($oldNomes)) {
                                    $oldNomes = [];
                                }
                                if (! is_array($oldAtuais)) {
                                    $oldAtuais = [];
                                }
                                if (! is_array($oldIds)) {
                                    $oldIds = [];
                                }
                                $ingredientesLinhas = [];
                                foreach ($oldNomes as $idx => $nome) {
                                    $fid = isset($oldIds[$idx]) ? (int) $oldIds[$idx] : 0;
                                    $atual = $oldAtuais[$idx] ?? '';
                                    $fotoUrl = null;
                                    if ($fid > 0 && $atual !== '') {
                                        $fotoUrl = route('publico.produto_ingrediente_foto', ['produtoIngrediente' => $fid], false).'?v='.time();
                                    }
                                    $ingredientesLinhas[] = [
                                        'nome' => $nome,
                                        'foto_atual' => $atual,
                                        'foto_url' => $fotoUrl,
                                        'id' => $fid > 0 ? $fid : null,
                                    ];
                                }
                            } else {
                                $ingredientesLinhas = $produto->ingredientes->map(function ($ing) {
                                    return [
                                        'nome' => $ing->nome,
                                        'foto_atual' => $ing->foto ?? '',
                                        'foto_url' => $ing->urlFoto(),
                                        'id' => $ing->id,
                                    ];
                                })->all();
                            }
                        @endphp
                        @include('partials.empresa.produto-ingredientes-form', ['linhas' => $ingredientesLinhas])
                        <div class="col-md-4">
                            <label class="form-label" for="max_ingredientes_retirar">Máx. ingredientes para retirar</label>
                            <input type="number" class="form-control @error('max_ingredientes_retirar') is-invalid @enderror" id="max_ingredientes_retirar" name="max_ingredientes_retirar" value="{{ old('max_ingredientes_retirar', $produto->max_ingredientes_retirar) }}" min="0" placeholder="Ex.: 2">
                            @error('max_ingredientes_retirar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Único limite da vitrine para retirada: quantos ingredientes da lista acima o cliente pode pedir para tirar (0 = não mostra retirada).</div>
                        </div>
                        @include('partials.empresa.produto-acrescimo-ingredientes-limites', ['produto' => $produto])
                        @php
                            $temErroOpcoesPagas = $errors->has('adicional_ids') || $errors->has('adicional_ids.*')
                                || $errors->has('permite_adicionais');
                            $opcoesPagasAberto = $temErroOpcoesPagas;
                        @endphp
                        @include('partials.empresa.produto-opcoes-pagas-form', [
                            'adicionais' => $adicionais,
                            'produto' => $produto,
                            'opcoesPagasAberto' => $opcoesPagasAberto,
                        ])
                        @include('partials.empresa.produto-fiscal-form', [
                            'produto' => $produto,
                            'fiscalConfig' => $fiscalConfig ?? null,
                        ])
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
    @push('scripts')
        @include('partials.empresa.produto-foto-preview')
    @endpush
@endsection
