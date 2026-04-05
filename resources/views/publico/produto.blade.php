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
                <div class="vf-card ratio ratio-1x1 bg-light d-flex align-items-center justify-content-center">
                    <i class="bi bi-cup-hot display-3 text-primary opacity-25"></i>
                </div>
            </div>
            <div class="col-md-6">
                @if ($produto->sku)
                    <span class="vf-badge bg-primary-subtle text-primary">SKU {{ $produto->sku }}</span>
                @endif
                <h1 class="h3 fw-bold mt-2">{{ $produto->nome }}</h1>
                <p class="text-muted" style="white-space: pre-wrap;">{{ $produto->descricao !== null && $produto->descricao !== '' ? $produto->descricao : 'Sem descrição cadastrada.' }}</p>
                <p class="h4 text-success mb-2">R$ {{ number_format((float) $produto->preco, 2, ',', '.') }}</p>
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
                    <form action="{{ route('publico.carrinho.adicionar', ['slug' => $slug]) }}" method="post" class="d-flex flex-wrap gap-2 align-items-end mb-4">
                        @csrf
                        <input type="hidden" name="produto_id" value="{{ $produto->id }}">
                        <div>
                            <label class="form-label small text-muted mb-1" for="qtd">Quantidade</label>
                            <input type="number" class="form-control" id="qtd" name="quantidade" value="1" min="1" max="{{ $produto->estoque !== null ? min(99, $produto->estoque) : 99 }}" style="max-width: 5rem;">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-cart-plus me-1"></i>Adicionar ao carrinho</button>
                    </form>
                @endif

                <div class="small text-muted"><a href="{{ route('publico.loja', ['slug' => $slug]) }}"><i class="bi bi-arrow-left me-1"></i>Voltar ao cardápio</a></div>
            </div>
        </div>
    </div>
@endsection
