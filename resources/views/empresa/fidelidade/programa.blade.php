@extends('layouts.empresa')

@section('title', 'Fidelidade')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('empresa.dashboard')],
        ['label' => 'Fidelidade', 'url' => route('empresa.fidelidade.programa')],
    ]])

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 fw-bold mb-0">Programa de fidelidade</h2>
        <a href="{{ route('empresa.fidelidade.cartoes') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-postcard me-1"></i>Cartões e selos
        </a>
    </div>

    <p class="text-muted small mb-4">
        Defina quantas compras geram a recompensa e se será um produto ou valor em desconto. O cliente acompanha na vitrine pública informando o celular.
        Quando existir integração com pedidos online, os selos poderão ser lançados automaticamente; por enquanto você registra cada compra na tela de cartões.
    </p>

    <div class="vf-card p-4" style="max-width: 42rem;">
        <form action="{{ route('empresa.fidelidade.programa.update') }}" method="post">
            @csrf
            @method('PUT')
            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" name="ativo" id="fid-ativo" value="1" {{ old('ativo', $programa->ativo) ? 'checked' : '' }}>
                <label class="form-check-label" for="fid-ativo">Programa ativo na vitrine</label>
            </div>
            <div class="mb-3">
                <label class="form-label" for="nome_exibicao">Nome na vitrine</label>
                <input type="text" class="form-control @error('nome_exibicao') is-invalid @enderror" id="nome_exibicao" name="nome_exibicao"
                       value="{{ old('nome_exibicao', $programa->nome_exibicao) }}" required>
                @error('nome_exibicao')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="pedidos_meta">Compras para ganhar a recompensa</label>
                <input type="number" class="form-control @error('pedidos_meta') is-invalid @enderror" id="pedidos_meta" name="pedidos_meta"
                       value="{{ old('pedidos_meta', $programa->pedidos_meta) }}" min="1" max="100" required>
                @error('pedidos_meta')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="tipo_recompensa">Tipo de recompensa</label>
                <select class="form-select @error('tipo_recompensa') is-invalid @enderror" id="tipo_recompensa" name="tipo_recompensa" required>
                    <option value="{{ \App\Models\FidelidadePrograma::TIPO_PRODUTO }}" @selected(old('tipo_recompensa', $programa->tipo_recompensa) === \App\Models\FidelidadePrograma::TIPO_PRODUTO)>Produto grátis (ou item definido)</option>
                    <option value="{{ \App\Models\FidelidadePrograma::TIPO_DESCONTO_VALOR }}" @selected(old('tipo_recompensa', $programa->tipo_recompensa) === \App\Models\FidelidadePrograma::TIPO_DESCONTO_VALOR)>Desconto em R$</option>
                </select>
                @error('tipo_recompensa')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3" id="wrap-produto">
                <label class="form-label" for="produto_id">Produto</label>
                <select class="form-select @error('produto_id') is-invalid @enderror" id="produto_id" name="produto_id">
                    <option value="">Selecione…</option>
                    @foreach ($produtos as $p)
                        <option value="{{ $p->id }}" @selected((string) old('produto_id', $programa->produto_id) === (string) $p->id)>{{ $p->nome }}</option>
                    @endforeach
                </select>
                @error('produto_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3 d-none" id="wrap-desconto">
                <label class="form-label" for="valor_desconto">Valor do desconto (R$)</label>
                <input type="number" step="0.01" min="0.01" class="form-control @error('valor_desconto') is-invalid @enderror" id="valor_desconto" name="valor_desconto"
                       value="{{ old('valor_desconto', $programa->valor_desconto) }}">
                @error('valor_desconto')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label class="form-label" for="texto_recompensa">Mensagem para o cliente (opcional)</label>
                <textarea class="form-control @error('texto_recompensa') is-invalid @enderror" id="texto_recompensa" name="texto_recompensa" rows="2" placeholder="Ex.: Válido na loja física ou no próximo pedido.">{{ old('texto_recompensa', $programa->texto_recompensa) }}</textarea>
                @error('texto_recompensa')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            @if ($empresa->slug)
                <p class="small text-muted mb-3">
                    Link na vitrine: <a href="{{ route('publico.fidelidade', ['slug' => $empresa->slug]) }}" target="_blank" rel="noopener">{{ url('/loja/'.$empresa->slug.'/fidelidade') }}</a>
                </p>
            @endif
            <button type="submit" class="btn btn-primary">Salvar programa</button>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var tipo = document.getElementById('tipo_recompensa');
            var wP = document.getElementById('wrap-produto');
            var wD = document.getElementById('wrap-desconto');
            function sync() {
                if (!tipo) return;
                var produto = tipo.value === '{{ \App\Models\FidelidadePrograma::TIPO_PRODUTO }}';
                wP.classList.toggle('d-none', !produto);
                wD.classList.toggle('d-none', produto);
            }
            tipo.addEventListener('change', sync);
            sync();
        })();
    </script>
@endpush
