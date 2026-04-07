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
                        <img src="{{ $produto->urlFoto() }}" alt="{{ $produto->nome }}" class="w-100 h-100 object-fit-cover">
                    @else
                        <div class="d-flex align-items-center justify-content-center w-100 h-100">
                            <i class="bi bi-cup-hot display-3 text-primary opacity-25"></i>
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-md-6">
                <h1 class="h3 fw-bold mt-2">{{ $produto->nome }}</h1>
                <p class="text-muted" style="white-space: pre-wrap;">{{ $produto->descricao !== null && $produto->descricao !== '' ? $produto->descricao : 'Sem descrição cadastrada.' }}</p>
                @php
                    $temAcrescimo = $produto->permite_adicionais && $produto->adicionais->where('tipo', \App\Models\Adicional::TIPO_ACRESCENTAR)->isNotEmpty();
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

                        @if ($produto->permite_adicionais && $produto->adicionais->isNotEmpty())
                            <div class="vf-card p-3 mb-3">
                                <h2 class="h6 fw-bold mb-2">Personalizar</h2>
                                @php $acres = $produto->adicionais->where('tipo', \App\Models\Adicional::TIPO_ACRESCENTAR); @endphp
                                @if ($acres->isNotEmpty())
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
                                @php $rets = $produto->adicionais->where('tipo', \App\Models\Adicional::TIPO_RETIRAR); @endphp
                                @if ($rets->isNotEmpty())
                                    <p class="small text-muted mb-2 mt-3">Retirar ingrediente</p>
                                    @foreach ($rets as $ad)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="adicional_ids[]" id="adicional_{{ $ad->id }}" value="{{ $ad->id }}">
                                            <label class="form-check-label" for="adicional_{{ $ad->id }}">Sem {{ $ad->nome }}</label>
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
@endsection
