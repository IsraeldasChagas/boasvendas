@php
    $nomeLoja = $empresa->nome ?? 'Loja';
    $slugNav = $slug ?? 'demo';
    $qtdCarrinho = $carrinhoContagem ?? 0;
    $enderecoLoja = $empresa?->endereco ? trim((string) $empresa->endereco) : '';
    $whatsRaw = $empresa?->whatsapp ? trim((string) $empresa->whatsapp) : '';
    $whatsDigits = $whatsRaw !== '' ? preg_replace('/\D+/', '', $whatsRaw) : '';
    if (is_string($whatsDigits) && ($whatsDigits !== '') && (strlen($whatsDigits) === 10 || strlen($whatsDigits) === 11)) {
        $whatsDigits = '55'.$whatsDigits;
    }
    $temContatoTopo = ($enderecoLoja !== '') || ($whatsDigits !== '');
@endphp
<header class="vf-publico-header sticky-top shadow-sm">
    <div class="container py-2">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <a href="{{ route('publico.loja', ['slug' => $slugNav]) }}" class="text-decoration-none text-dark fw-bold me-2 d-flex align-items-center gap-1" style="min-width: 0;">
                @if ($empresa && $empresa->urlLogo())
                    <img src="{{ $empresa->urlLogo() }}" alt="" width="66" height="66" class="me-2 rounded bg-white border" style="object-fit: contain;">
                @else
                    <i class="bi bi-shop text-primary me-1"></i>
                @endif
                <span class="vf-store-name">{{ $nomeLoja }}</span>
            </a>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                @if ($temContatoTopo)
                    <button class="btn btn-sm btn-outline-secondary" type="button"
                            data-bs-toggle="collapse" data-bs-target="#vf-store-info"
                            aria-expanded="false" aria-controls="vf-store-info" title="Ver contato">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                @endif
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

        @if ($temContatoTopo)
            <div class="collapse mt-2" id="vf-store-info">
                <div class="small text-muted d-flex flex-column gap-1">
                    @if ($enderecoLoja !== '')
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-geo-alt mt-1"></i>
                            <div style="white-space: pre-wrap;">{{ $enderecoLoja }}</div>
                        </div>
                    @endif
                    @if ($whatsDigits !== '')
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-whatsapp"></i>
                            <a class="text-decoration-none" href="{{ 'https://wa.me/'.$whatsDigits }}" target="_blank" rel="noopener">
                                {{ $whatsRaw }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</header>
