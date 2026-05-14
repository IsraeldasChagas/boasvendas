@extends('layouts.publico')

@section('title', $empresa->nome)

@section('content')
    @php
        $lojaColAcrescMinMax = \Illuminate\Support\Facades\Schema::hasColumn('produtos', 'acrescimo_escolhas_min');
    @endphp
    <div class="container">
        @if ($empresa->fidelidadePrograma && $empresa->fidelidadePrograma->ativo)
            <div class="mb-3 d-flex flex-wrap align-items-center gap-2">
                <a href="{{ route('publico.fidelidade', ['slug' => $slug]) }}" class="vf-badge bg-primary-subtle text-primary text-decoration-none">
                    <i class="bi bi-award me-1"></i>Cartão fidelidade
                </a>
            </div>
        @endif

        @if ($mostrarBanner ?? false)
            <div class="vf-loja-banner vf-loja-banner-card mb-3">
                @if (($bannerSlides ?? collect())->isNotEmpty())
                    @php
                        $carouselId = 'vfLojaBannerEmp'.$empresa->id;
                        $slideCount = $bannerSlides->count();
                        $showArrows = $slideCount > 1;
                    @endphp
                    <div id="{{ $carouselId }}" class="carousel slide vf-loja-banner-carousel vf-card vf-loja-banner-media rounded-3 overflow-hidden shadow-sm border border-primary-subtle position-relative{{ $showArrows ? ' carousel-fade vf-loja-banner-carousel--fire' : '' }}"
                        @if ($showArrows)
                            data-bs-ride="carousel"
                            data-bs-interval="4500"
                            data-bs-pause="hover"
                            data-bs-wrap="true"
                        @else
                            data-bs-ride="false"
                            data-bs-interval="false"
                        @endif>
                        <div class="carousel-inner">
                            @foreach ($bannerSlides as $idx => $slide)
                                @php $tipoSlide = $slide['tipo'] ?? 'produto'; @endphp
                                <div class="carousel-item {{ $idx === 0 ? 'active' : '' }}">
                                    <div class="vf-loja-banner-slide-frame">
                                        <img src="{{ $slide['url'] }}" alt="{{ $tipoSlide === 'upload' ? $empresa->nome : ($slide['nome'] ?: 'Produto') }}" class="vf-loja-banner-img d-block w-100" loading="{{ $idx === 0 ? 'eager' : 'lazy' }}" decoding="async">
                                    </div>
                                    @if ($tipoSlide === 'produto' && ($bannerCategoria ?? null))
                                        <div class="position-absolute top-0 start-0 end-0 vf-loja-banner-scrim vf-loja-banner-scrim--category px-3 py-2 text-white text-start">
                                            <span class="h5 fw-bold mb-0 d-block">{{ $bannerCategoria->nome }}</span>
                                        </div>
                                    @endif
                                    <div class="position-absolute bottom-0 start-0 end-0 vf-loja-banner-scrim vf-loja-banner-scrim--carousel px-3 py-3 text-white text-start">
                                        @if ($tipoSlide === 'produto' && ($bannerCategoria ?? null))
                                            @if ($slideCount > 1)
                                                <span class="small d-block opacity-90 text-truncate">{{ $slide['nome'] }}</span>
                                            @endif
                                            <a href="{{ route('publico.loja', ['slug' => $slug, 'categoria_id' => $bannerCategoria->id]) }}" class="small fw-semibold text-white text-decoration-underline {{ $slideCount > 1 ? 'mt-1' : '' }} d-inline-block">Ver todos os produtos desta categoria</a>
                                        @else
                                            @if ($bannerCategoria ?? null)
                                                <a href="{{ route('publico.loja', ['slug' => $slug, 'categoria_id' => $bannerCategoria->id]) }}" class="small fw-semibold text-white text-decoration-underline d-inline-block">Ver {{ $bannerCategoria->nome }}</a>
                                            @else
                                                <a href="{{ route('publico.loja', ['slug' => $slug]) }}" class="small fw-semibold text-white text-decoration-underline d-inline-block">Ver cardápio</a>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if ($showArrows)
                            <button class="carousel-control-prev vf-loja-banner-ctrl" type="button" data-bs-target="#{{ $carouselId }}" data-bs-slide="prev" aria-label="Imagem anterior">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            </button>
                            <button class="carousel-control-next vf-loja-banner-ctrl" type="button" data-bs-target="#{{ $carouselId }}" data-bs-slide="next" aria-label="Próxima imagem">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            </button>
                        @endif
                    </div>
                @elseif ($bannerCategoria ?? null)
                    <a href="{{ route('publico.loja', ['slug' => $slug, 'categoria_id' => $bannerCategoria->id]) }}" class="d-block text-decoration-none">
                        <div class="vf-card vf-loja-banner-media vf-loja-banner-fallback rounded-3 overflow-hidden border-0 shadow-sm bg-primary text-white d-flex flex-column align-items-start justify-content-start text-start px-3 pt-3 pb-3">
                            <span class="h5 fw-bold mb-2 d-block">{{ $bannerCategoria->nome }}</span>
                            <span class="small opacity-90 mt-auto">Ver produtos desta categoria →</span>
                        </div>
                    </a>
                @endif
            </div>
        @endif

        <form action="{{ route('publico.loja', ['slug' => $slug]) }}" method="get" class="vf-filter-bar mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-6 col-lg-4">
                    <label class="form-label small text-muted mb-1" for="loja-cat">Categoria</label>
                    <select class="form-select form-select-sm" id="loja-cat" name="categoria_id" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        <option value="adicionais" @selected((string) request('categoria_id') === 'adicionais')>Adicionais</option>
                        @foreach ($categorias as $cat)
                            <option value="{{ $cat->id }}" @selected((string) request('categoria_id') === (string) $cat->id)>{{ $cat->nome }}</option>
                        @endforeach
                    </select>
                </div>
                @if (request()->filled('categoria_id'))
                    <div class="col-auto">
                        <a href="{{ route('publico.loja', ['slug' => $slug]) }}" class="btn btn-sm btn-outline-secondary">Limpar</a>
                    </div>
                @endif
            </div>
        </form>

        @if (isset($adicionaisCatalogo) && $adicionaisCatalogo->isNotEmpty())
            <div class="mb-4">
                <h2 class="h6 fw-bold mb-2">
                    @if (! empty($soloAdicionais))
                        Adicionais
                    @else
                        Adicionais nesta parte do cardápio
                    @endif
                </h2>
                <p class="small text-muted mb-3">
                    @if (! empty($soloAdicionais))
                        Extras de acréscimo disponíveis na loja para levar à parte.
                    @else
                        Opções de acréscimo ligadas aos produtos @if (request()->filled('categoria_id') && ctype_digit((string) request('categoria_id')))desta categoria @elseem exibição @endif.
                        Você pode levar também <strong>à parte</strong> — o mesmo item do cadastro de adicionais.
                    @endif
                </p>
                <div class="row g-3">
                    @foreach ($adicionaisCatalogo as $adCat)
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="vf-card vf-loja-adicional-card h-100 overflow-hidden d-flex flex-column">
                                <div class="ratio ratio-4x3 bg-light border-bottom overflow-hidden">
                                    @if ($adCat->urlFoto())
                                        <img src="{{ $adCat->urlFoto() }}" alt="" class="w-100 h-100 object-fit-cover">
                                    @else
                                        <div class="d-flex align-items-center justify-content-center w-100 h-100 text-muted">
                                            <i class="bi bi-plus-lg fs-3 opacity-50"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="p-3 flex-grow-1 d-flex flex-column">
                                    <div class="fw-semibold small mb-1">{{ $adCat->nome }}</div>
                                    <div class="text-success fw-bold small mb-3">R$ {{ number_format((float) $adCat->preco, 2, ',', '.') }}</div>
                                    <form action="{{ route('publico.carrinho.adicionar-adicional', ['slug' => $slug]) }}" method="post" class="mt-auto">
                                        @csrf
                                        <input type="hidden" name="adicional_id" value="{{ $adCat->id }}">
                                        @if (request()->filled('categoria_id') && ctype_digit((string) request('categoria_id')))
                                            <input type="hidden" name="contexto_categoria_id" value="{{ request('categoria_id') }}">
                                        @endif
                                        <button type="submit" class="btn btn-primary btn-sm w-100">Adicionar ao carrinho</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if (empty($soloAdicionais))
        <div class="row g-3">
            @forelse ($produtos as $pr)
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="{{ route('publico.produto', ['slug' => $slug, 'produto_id' => $pr->id]) }}" class="text-decoration-none text-dark">
                        <div class="vf-card vf-product-card h-100 overflow-hidden">
                            <div class="ratio ratio-4x3 bg-primary-subtle overflow-hidden">
                                @if ($pr->urlFoto())
                                    <img src="{{ $pr->urlFoto() }}" alt="" class="w-100 h-100 object-fit-cover"
                                         onerror="this.style.display='none'; this.parentElement.querySelector('[data-fallback]').classList.remove('d-none');">
                                @else
                                    <div class="d-flex align-items-center justify-content-center w-100 h-100">
                                        <i class="bi bi-image text-primary opacity-50 fs-1"></i>
                                    </div>
                                @endif
                                <div class="d-none d-flex align-items-center justify-content-center w-100 h-100" data-fallback>
                                    <i class="bi bi-image text-primary opacity-50 fs-1"></i>
                                </div>
                            </div>
                            <div class="p-3">
                                <div class="fw-semibold">{{ $pr->nome }}</div>
                                @if ($pr->categoria)
                                    <div class="small text-muted">{{ $pr->categoria->nome }}</div>
                                @endif
                                @if (($pr->permite_adicionais && ($pr->adicionais_acrescimo_count ?? 0) > 0) || (($pr->ingredientes_count ?? 0) > 0))
                                    <div class="small mt-1"><span class="vf-badge bg-info-subtle text-info">Personalizável</span></div>
                                @endif
                                @if ($lojaColAcrescMinMax && $pr->permite_adicionais && ($pr->adicionais_acrescimo_count ?? 0) > 0)
                                    @php
                                        $lojaMinA = $pr->acrescimo_escolhas_min;
                                        $lojaMaxA = $pr->acrescimo_escolhas_max;
                                    @endphp
                                    @if ($lojaMinA !== null || $lojaMaxA !== null)
                                        <div class="small text-muted mt-1 lh-sm">
                                            <span class="vf-personalizar-limite-chip d-inline-block">Opções — mín. {{ $lojaMinA ?? '—' }} · máx. {{ $lojaMaxA ?? '—' }}</span>
                                        </div>
                                    @endif
                                @endif
                                <div class="text-success fw-bold mt-1">R$ {{ number_format((float) $pr->preco, 2, ',', '.') }}</div>
                            </div>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-12">
                    <div class="vf-card p-4 text-center text-muted">
                        Nenhum produto disponível na vitrine no momento.
                    </div>
                </div>
            @endforelse
        </div>

        @if ($produtos->hasPages())
            <div class="mt-4 d-flex justify-content-center">{{ $produtos->links() }}</div>
        @endif
        @else
            <p class="small text-muted mb-4">Para ver os pratos, escolha <strong>Todas</strong> ou uma categoria no filtro acima.</p>
        @endif
    </div>
@endsection

@if (($mostrarBanner ?? false) && ($bannerSlides ?? collect())->count() > 1)
    @push('scripts')
        <script>
            (function () {
                var el = document.getElementById('vfLojaBannerEmp{{ $empresa->id }}');
                if (!el || typeof bootstrap === 'undefined') return;
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    el.setAttribute('data-bs-interval', 'false');
                    el.setAttribute('data-bs-ride', 'false');
                    el.classList.remove('vf-loja-banner-carousel--fire', 'carousel-fade');
                    return;
                }
                var t;
                el.addEventListener('slide.bs.carousel', function () {
                    clearTimeout(t);
                    el.classList.add('vf-loja-banner--ignite');
                });
                el.addEventListener('slid.bs.carousel', function () {
                    t = setTimeout(function () {
                        el.classList.remove('vf-loja-banner--ignite');
                    }, 620);
                });
            })();
        </script>
    @endpush
@endif
