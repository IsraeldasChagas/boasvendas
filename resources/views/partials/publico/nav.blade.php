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
    $igUrl = $empresa ? trim((string) ($empresa->instagram_url ?? '')) : '';
    $fbUrl = $empresa ? trim((string) ($empresa->facebook_url ?? '')) : '';
    $temContatoTopo = ($enderecoLoja !== '')
        || ($whatsDigits !== '')
        || ($igUrl !== '')
        || ($fbUrl !== '');
@endphp
<header class="vf-publico-header sticky-top shadow-sm">
    <div class="container py-2">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <div class="d-flex align-items-center gap-2 gap-sm-3 min-width-0 flex-grow-1 flex-wrap">
                <a href="{{ route('publico.loja', ['slug' => $slugNav]) }}"
                   class="vf-store-brand-link vf-store-brand--unidade-atual text-decoration-none text-dark fw-bold d-flex align-items-center gap-2 gap-md-3 min-width-0"
                   title="Você está nesta unidade — cardápio e pedidos desta loja.">
                    @if ($empresa && $empresa->urlLogo())
                        <img src="{{ $empresa->urlLogo() }}" alt="" width="66" height="66" class="vf-store-brand-logo rounded bg-white border flex-shrink-0" style="object-fit: contain;">
                    @else
                        <i class="bi bi-shop text-primary vf-store-brand-logo-placeholder flex-shrink-0"></i>
                    @endif
                        <span class="vf-store-brand-text d-flex flex-column justify-content-center align-items-start gap-1 gap-md-2 min-width-0">
                            <span class="vf-store-name d-inline-flex align-items-center gap-1 flex-wrap">
                                {{ $nomeLoja }}
                                <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle small fw-normal flex-shrink-0">Aqui</span>
                            </span>
                        @if ($empresa)
                            @if ($empresa->loja_aberta ?? true)
                                <span class="badge rounded-pill bg-success vf-loja-status-badge align-self-start">Aberta</span>
                            @else
                                <span class="badge rounded-pill bg-danger vf-loja-status-badge align-self-start">Fechada</span>
                            @endif
                        @endif
                    </span>
                </a>
                @if ($empresa && $empresa->exibirFilialTopo())
                    @php
                        $filialHref = trim((string) ($empresa->loja_filial_link_url ?? ''));
                    @endphp
                    <span class="align-self-stretch border-start d-none d-sm-block mx-1 opacity-25" style="width: 1px; min-height: 3.5rem;" aria-hidden="true"></span>
                    @if ($filialHref !== '')
                        <a href="{{ $filialHref }}" target="_blank" rel="noopener noreferrer"
                           class="vf-store-brand-link vf-store-brand--outra-unidade text-decoration-none text-dark fw-bold d-flex align-items-center gap-2 gap-md-3 min-width-0 px-1 rounded-2"
                           title="Outra unidade — abre em nova aba para o site ou cardápio da filial.">
                    @else
                        <div class="vf-store-brand-link vf-store-brand--outra-unidade text-dark fw-bold d-flex align-items-center gap-2 gap-md-3 min-width-0 px-1 rounded-2"
                             role="group"
                             aria-label="Outra unidade"
                             title="Outra unidade da rede (identificação).">
                    @endif
                            @if ($empresa->urlLogoFilial())
                                <img src="{{ $empresa->urlLogoFilial() }}" alt="" width="66" height="66" class="vf-store-brand-logo rounded bg-white border flex-shrink-0" style="object-fit: contain;">
                            @else
                                <i class="bi bi-shop-window text-primary vf-store-brand-logo-placeholder flex-shrink-0" aria-hidden="true"></i>
                            @endif
                            <span class="vf-store-brand-text d-flex flex-column justify-content-center align-items-start gap-1 gap-md-2 min-width-0">
                                <span class="vf-store-name text-truncate w-100 d-inline-flex align-items-center gap-1">
                                    {{ $empresa->loja_filial_nome }}
                                    <span class="badge rounded-pill bg-secondary-subtle text-secondary border small fw-normal flex-shrink-0">Só ver</span>
                                </span>
                                @if ($empresa->loja_aberta ?? true)
                                    <span class="badge rounded-pill bg-success vf-loja-status-badge align-self-start">Aberta</span>
                                @else
                                    <span class="badge rounded-pill bg-danger vf-loja-status-badge align-self-start">Fechada</span>
                                @endif
                            </span>
                    @if ($filialHref !== '')
                        </a>
                    @else
                        </div>
                    @endif
                @endif
            </div>
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
                    @if ($igUrl !== '')
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-instagram text-danger"></i>
                            <a class="text-decoration-none" href="{{ $igUrl }}" target="_blank" rel="noopener noreferrer">Instagram</a>
                        </div>
                    @endif
                    @if ($fbUrl !== '')
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-facebook text-primary"></i>
                            <a class="text-decoration-none" href="{{ $fbUrl }}" target="_blank" rel="noopener noreferrer">Facebook</a>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</header>
