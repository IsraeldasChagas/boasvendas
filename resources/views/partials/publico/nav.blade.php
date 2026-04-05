@php
    $nomeLoja = $empresa->nome ?? 'Loja';
    $slugNav = $slug ?? 'demo';
    $qtdCarrinho = $carrinhoContagem ?? 0;
@endphp
<header class="vf-publico-header sticky-top shadow-sm">
    <div class="container py-2">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <a href="{{ route('publico.loja', ['slug' => $slugNav]) }}" class="text-decoration-none text-dark fw-bold text-truncate me-2">
                <i class="bi bi-shop text-primary me-1"></i><span class="d-none d-sm-inline">{{ $nomeLoja }}</span><span class="d-sm-none">Loja</span>
            </a>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                @if ($empresa && $empresa->fidelidadePrograma && $empresa->fidelidadePrograma->ativo)
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('publico.fidelidade', ['slug' => $slugNav]) }}" title="Cartão fidelidade">
                        <i class="bi bi-award"></i><span class="d-none d-md-inline ms-1">Fidelidade</span>
                    </a>
                @endif
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('publico.acompanhar', ['slug' => $slugNav]) }}">
                    <i class="bi bi-search me-1"></i><span class="d-none d-sm-inline">Pedido</span>
                </a>
                <a class="btn btn-sm btn-primary position-relative" href="{{ route('publico.carrinho', ['slug' => $slugNav]) }}">
                    <i class="bi bi-cart3"></i>
                    @if ($qtdCarrinho > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $qtdCarrinho > 99 ? '99+' : $qtdCarrinho }}</span>
                    @endif
                </a>
            </div>
        </div>
    </div>
</header>
