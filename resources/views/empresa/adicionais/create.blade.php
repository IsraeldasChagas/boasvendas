@extends('layouts.empresa')

@section('title', 'Novo adicional')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Adicionais', 'url' => route('empresa.adicionais.index')],
        ['label' => 'Novo', 'url' => route('empresa.adicionais.create')],
    ]])
    <div class="row">
        <div class="col-lg-8">
            <div class="vf-card p-4">
                <h2 class="h5 fw-bold mb-4">Novo adicional</h2>
                <form action="{{ route('empresa.adicionais.store') }}" method="post" id="form-adicional">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label" for="nome">Nome</label>
                            <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome') }}" placeholder="Ex.: Bacon extra, Sem cebola" required>
                            @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="ordem">Ordem</label>
                            <input type="number" class="form-control @error('ordem') is-invalid @enderror" id="ordem" name="ordem" value="{{ old('ordem', 0) }}" min="0">
                            @error('ordem')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="tipo">Tipo</label>
                            <select class="form-select @error('tipo') is-invalid @enderror" id="tipo" name="tipo" required>
                                @foreach (\App\Models\Adicional::tiposRotulos() as $val => $rotulo)
                                    <option value="{{ $val }}" @selected(old('tipo', \App\Models\Adicional::TIPO_ACRESCENTAR) === $val)>{{ $rotulo }}</option>
                                @endforeach
                            </select>
                            @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6" id="wrap-preco">
                            <label class="form-label" for="preco">Preço extra (R$)</label>
                            <input type="number" class="form-control @error('preco') is-invalid @enderror" id="preco" name="preco" value="{{ old('preco', 0) }}" min="0" step="0.01" required>
                            @error('preco')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Só vale para “Acrescentar”. Retirar fica com preço zero.</div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="ativo" id="ativo" value="1" {{ old('ativo', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="ativo">Ativo (pode ser usado em produtos)</label>
                            </div>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Salvar</button>
                            <a href="{{ route('empresa.adicionais.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            (function () {
                var tipo = document.getElementById('tipo');
                var wrap = document.getElementById('wrap-preco');
                var preco = document.getElementById('preco');
                function sync() {
                    if (!tipo || !wrap) return;
                    var retirar = tipo.value === '{{ \App\Models\Adicional::TIPO_RETIRAR }}';
                    wrap.style.display = retirar ? 'none' : 'block';
                    if (retirar && preco) preco.value = '0';
                }
                if (tipo) tipo.addEventListener('change', sync);
                sync();
            })();
        </script>
    @endpush
@endsection
