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
                <form action="{{ route('empresa.produtos.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="foto">Foto do produto</label>
                            <input type="file" class="form-control @error('foto') is-invalid @enderror" id="foto" name="foto" accept="image/jpeg,image/png,image/webp,image/gif">
                            @error('foto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Opcional. JPG, PNG, WebP ou GIF, até 3&nbsp;MB. Aparece no cardápio online. No servidor rode <code>php artisan storage:link</code> uma vez se a foto não abrir após salvar.</div>
                            <div id="foto-preview-wrap" class="mt-2 d-none">
                                <span class="small text-muted d-block mb-1">Prévia</span>
                                <img id="foto-preview" src="" alt="Prévia da foto" class="rounded border" width="160" height="160" style="max-height: 160px; width: auto; object-fit: cover;">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="nome">Nome</label>
                            <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome') }}" placeholder="Ex.: X-Burger" required>
                            @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">O <strong>código interno</strong> (identificação no estoque) será gerado automaticamente ao salvar.</div>
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
                        @if (\Illuminate\Support\Facades\Schema::hasColumn('produtos', 'ingredientes_retirar_ui'))
                            @php
                                $uiRetirarIng = old('ingredientes_retirar_ui', \App\Models\Produto::ING_RETIRAR_UI_STEPPER);
                            @endphp
                            <div class="col-12">
                                <label class="form-label">Na loja: cliente marca retirada de ingredientes com</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="ingredientes_retirar_ui" id="ingredientes_ui_stepper" value="{{ \App\Models\Produto::ING_RETIRAR_UI_STEPPER }}" @checked($uiRetirarIng === \App\Models\Produto::ING_RETIRAR_UI_STEPPER)>
                                    <label class="form-check-label" for="ingredientes_ui_stepper">Botões − e + (quantidade por ingrediente)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="ingredientes_retirar_ui" id="ingredientes_ui_checkbox" value="{{ \App\Models\Produto::ING_RETIRAR_UI_CHECKBOX }}" @checked($uiRetirarIng === \App\Models\Produto::ING_RETIRAR_UI_CHECKBOX)>
                                    <label class="form-check-label" for="ingredientes_ui_checkbox">Caixas de seleção (marcar o que retirar)</label>
                                </div>
                                <div class="form-text">Usado quando há ingredientes do prato e “máx. para retirar” maior que zero. Cada marcação em checkbox conta 1 no limite.</div>
                            </div>
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
                                $ingredientesLinhas = [];
                            }
                        @endphp
                        @include('partials.empresa.produto-ingredientes-form', ['linhas' => $ingredientesLinhas])
                        <div class="col-md-4">
                            <label class="form-label" for="max_ingredientes_retirar">Máx. ingredientes para retirar</label>
                            <input type="number" class="form-control @error('max_ingredientes_retirar') is-invalid @enderror" id="max_ingredientes_retirar" name="max_ingredientes_retirar" value="{{ old('max_ingredientes_retirar') }}" min="0" placeholder="Ex.: 2">
                            @error('max_ingredientes_retirar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Obrigatório se houver ingredientes (use 0 se não quiser permitir retirada).</div>
                        </div>
                        @php
                            $temErroOpcoesPagas = $errors->has('adicional_ids') || $errors->has('adicional_ids.*')
                                || $errors->has('permite_adicionais')
                                || $errors->has('acrescimo_escolhas_min') || $errors->has('acrescimo_escolhas_max');
                            $opcoesPagasAberto = $temErroOpcoesPagas;
                        @endphp
                        @include('partials.empresa.produto-opcoes-pagas-form', [
                            'adicionais' => $adicionais,
                            'produto' => null,
                            'opcoesPagasAberto' => $opcoesPagasAberto,
                        ])
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
    @push('scripts')
        @include('partials.empresa.produto-foto-preview')
    @endpush
@endsection
