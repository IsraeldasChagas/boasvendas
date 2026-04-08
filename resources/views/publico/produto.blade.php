@extends('layouts.publico')

@section('title', $produto->nome.' — '.$empresa->nome)

@section('content')
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="{{ route('publico.loja', ['slug' => $slug]) }}">Cardápio</a></li>
                @if ($produto->categoria)
                    <li class="breadcrumb-item"><a href="{{ route('publico.loja', ['slug' => $slug, 'categoria_id' => $produto->categoria_id]) }}">{{ $produto->categoria->nome }}</a></li>
                @endif
                <li class="breadcrumb-item active" aria-current="page">{{ $produto->nome }}</li>
            </ol>
        </nav>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="vf-card ratio ratio-1x1 bg-light overflow-hidden">
                    @if ($produto->urlFoto())
                        <img src="{{ $produto->urlFoto() }}" alt="{{ $produto->nome }}" class="w-100 h-100 object-fit-cover"
                             onerror="this.style.display='none'; this.parentElement.querySelector('[data-fallback]').classList.remove('d-none');">
                    @else
                        <div class="d-flex align-items-center justify-content-center w-100 h-100">
                            <i class="bi bi-cup-hot display-3 text-primary opacity-25"></i>
                        </div>
                    @endif
                    <div class="d-none d-flex align-items-center justify-content-center w-100 h-100" data-fallback>
                        <i class="bi bi-cup-hot display-3 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <h1 class="h3 fw-bold mt-2">{{ $produto->nome }}</h1>
                <p class="text-muted" style="white-space: pre-wrap;">{{ $produto->descricao !== null && $produto->descricao !== '' ? $produto->descricao : 'Sem descrição cadastrada.' }}</p>
                @php
                    $acres = $produto->adicionais->where('tipo', \App\Models\Adicional::TIPO_ACRESCENTAR);
                    $temAcrescimo = $produto->permite_adicionais && $acres->isNotEmpty();
                    $maxRet = (int) ($produto->max_ingredientes_retirar ?? 0);
                    $temRetirarIng = $produto->ingredientes->isNotEmpty() && $maxRet > 0;
                    $temPersonalizar = ($produto->permite_adicionais && $acres->isNotEmpty()) || $temRetirarIng;
                @endphp
                <p class="h4 text-success mb-1">R$ {{ number_format((float) $produto->preco, 2, ',', '.') }}</p>
                @if ($temAcrescimo)
                    <p class="small text-muted mb-2">Preço base; acréscimos opcionais aparecem no total ao escolher abaixo.</p>
                @endif
                @if ($produto->estoque !== null)
                    <p class="small text-muted mb-3">
                        @if ($produto->estoque <= 0)
                            <span class="text-danger fw-semibold">Indisponível no momento.</span>
                        @else
                            {{ $produto->estoque }} unidade(s) disponível(is).
                        @endif
                    </p>
                @endif

                @if ($produto->estoque === null || $produto->estoque > 0)
                    <form action="{{ route('publico.carrinho.adicionar', ['slug' => $slug]) }}" method="post" class="mb-4">
                        @csrf
                        <input type="hidden" name="produto_id" value="{{ $produto->id }}">

                        @if ($temPersonalizar)
                            <div class="vf-card p-3 mb-3">
                                <h2 class="h6 fw-bold mb-2">Personalizar</h2>
                                @if ($temRetirarIng)
                                    <p class="small text-muted mb-3 border-start border-3 border-secondary-subtle ps-2">Retirar ingredientes é opcional e <strong>não reduz</strong> o valor do produto.</p>
                                @endif
                                @if ($produto->permite_adicionais && $acres->isNotEmpty())
                                    <p class="small text-muted mb-2">Acrescentar</p>
                                    @foreach ($acres as $ad)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="adicional_ids[]" id="adicional_{{ $ad->id }}" value="{{ $ad->id }}">
                                            <label class="form-check-label" for="adicional_{{ $ad->id }}">
                                                {{ $ad->nome }}
                                                @if ((float) $ad->preco > 0)
                                                    <span class="text-success">(+ R$ {{ number_format((float) $ad->preco, 2, ',', '.') }})</span>
                                                @endif
                                            </label>
                                        </div>
                                    @endforeach
                                @endif
                                @if ($temRetirarIng)
                                    <p class="small text-muted mb-2 mt-3">Retirar ingrediente <span class="text-muted">(até {{ $maxRet }})</span></p>
                                    @foreach ($produto->ingredientes as $ing)
                                        <div class="form-check">
                                            <input class="form-check-input vf-retirar-ing" type="checkbox" name="retirar_ingrediente_ids[]" id="ing_{{ $ing->id }}" value="{{ $ing->id }}" data-max="{{ $maxRet }}">
                                            <label class="form-check-label" for="ing_{{ $ing->id }}">Sem {{ $ing->nome }}</label>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        @endif

                        <div class="d-flex flex-wrap gap-2 align-items-end">
                            <div>
                                <label class="form-label small text-muted mb-1" for="qtd">Quantidade</label>
                                <input type="number" class="form-control" id="qtd" name="quantidade" value="1" min="1" max="{{ $produto->estoque !== null ? min(99, $produto->estoque) : 99 }}" style="max-width: 5rem;">
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-cart-plus me-1"></i>Adicionar ao carrinho</button>
                        </div>
                    </form>
                @endif

                <div class="small text-muted"><a href="{{ route('publico.loja', ['slug' => $slug]) }}"><i class="bi bi-arrow-left me-1"></i>Voltar ao cardápio</a></div>
            </div>
        </div>
    </div>
    @if ($temRetirarIng ?? false)
        @push('scripts')
            <script>
                (function () {
                    const form = document.querySelector('form[action*="carrinho.adicionar"]');
                    if (!form) return;
                    const max = parseInt(form.querySelector('.vf-retirar-ing')?.dataset.max || '0', 10);
                    if (!max) return;
                    form.addEventListener('change', function (e) {
                        if (!e.target.classList.contains('vf-retirar-ing')) return;
                        const checked = form.querySelectorAll('.vf-retirar-ing:checked');
                        if (checked.length > max) {
                            e.target.checked = false;
                            alert('Você pode escolher no máximo ' + max + ' ingrediente(s) para retirar.');
                        }
                    });
                })();
            </script>
        @endpush
    @endif
@endsection
