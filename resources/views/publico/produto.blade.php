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
            <div class="col-12 col-md-6">
                <div class="vf-card overflow-hidden vf-produto-pagina-foto vf-produto-pagina-foto--hero w-100">
                    <div class="vf-produto-pagina-foto__frame">
                        @if ($produto->urlFoto())
                            <img src="{{ $produto->urlFoto() }}" alt="{{ $produto->nome }}"
                                class="vf-produto-pagina-foto__img"
                                width="800" height="800"
                                loading="eager" fetchpriority="high" decoding="async"
                                onerror="this.classList.add('d-none'); var f=this.nextElementSibling; if(f) f.classList.remove('d-none');">
                            <div class="vf-produto-pagina-foto__placeholder d-none" data-fallback aria-hidden="true">
                                <i class="bi bi-cup-hot display-3 text-primary opacity-25" aria-hidden="true"></i>
                            </div>
                        @else
                            <div class="vf-produto-pagina-foto__placeholder" aria-hidden="true">
                                <i class="bi bi-cup-hot display-3 text-primary opacity-25" aria-hidden="true"></i>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="d-flex flex-column flex-md-row flex-md-wrap align-items-start justify-content-between gap-3 mt-2 mb-0">
                    <h1 class="h3 fw-bold mb-0">{{ $produto->nome }}</h1>
                    @php
                        $vfShareProdutoUrl = route('publico.produto', ['slug' => $slug, 'produto_id' => $produto->id]);
                        $vfWaTexto = $produto->nome.' — '.$empresa->nome."\n".$vfShareProdutoUrl;
                        $vfWaHref = 'https://wa.me/?text='.rawurlencode($vfWaTexto);
                        $vfFbHref = 'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($vfShareProdutoUrl);
                    @endphp
                    <div class="vf-produto-share d-flex flex-wrap align-items-center justify-content-between justify-content-md-end gap-2 gap-md-3 align-self-stretch w-100 w-md-auto pt-1 pt-md-0" role="group" aria-label="Compartilhar este produto">
                        <a href="{{ $vfWaHref }}" target="_blank" rel="noopener noreferrer"
                           class="btn btn-success vf-produto-share__btn rounded-3"
                           title="WhatsApp"
                           aria-label="Compartilhar no WhatsApp">
                            <i class="bi bi-whatsapp vf-produto-share__ico" aria-hidden="true"></i><span class="vf-produto-share__label ms-1">WhatsApp</span>
                        </a>
                        <a href="{{ $vfFbHref }}" target="_blank" rel="noopener noreferrer"
                           class="btn btn-primary vf-produto-share__btn rounded-3"
                           title="Facebook"
                           aria-label="Compartilhar no Facebook">
                            <i class="bi bi-facebook vf-produto-share__ico" aria-hidden="true"></i><span class="vf-produto-share__label ms-1">Facebook</span>
                        </a>
                        <button type="button"
                            class="btn text-white border-0 vf-produto-share__btn vf-share-instagram rounded-3"
                            style="background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);"
                            data-share-url="{{ $vfShareProdutoUrl }}"
                            title="Instagram — copia o link para você colar no app"
                            aria-label="Copiar link do produto para compartilhar no Instagram">
                            <i class="bi bi-instagram vf-produto-share__ico" aria-hidden="true"></i><span class="vf-produto-share__label ms-1 vf-share-instagram-label">Instagram</span>
                        </button>
                    </div>
                </div>
                @if ($produto->estoque === null || $produto->estoque > 0)
                    <div class="vf-produto-estrelas mb-3" id="vf-produto-estrelas-wrap">
                        <span class="small text-muted d-block mb-1">Sua nota <span class="fw-normal">(opcional)</span></span>
                        <div class="d-inline-flex align-items-center vf-estrelas-grupo" role="group" aria-label="Dar de 1 a 5 estrelas — clique de novo na última estrela da nota para baixar um ponto; com 1 estrela, remove a nota">
                            @for ($s = 1; $s <= 5; $s++)
                                <button type="button" class="btn btn-link p-1 lh-1 vf-estrela-produto-btn text-warning text-decoration-none border-0"
                                    data-vf-estrela="{{ $s }}"
                                    aria-label="{{ $s }} estrela{{ $s > 1 ? 's' : '' }}">
                                    <i class="bi bi-star vf-estrela-produto-ico fs-5" aria-hidden="true"></i>
                                </button>
                            @endfor
                        </div>
                        <input type="hidden" name="nota_produto" id="vf_nota_produto" form="vf-form-carrinho-produto" value="{{ old('nota_produto') }}">
                        @error('nota_produto')
                            <div class="small text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                @endif
                <p class="text-muted" style="white-space: pre-wrap;">{{ $produto->descricao !== null && $produto->descricao !== '' ? $produto->descricao : 'Sem descrição cadastrada.' }}</p>
                @php
                    use Illuminate\Support\Facades\Schema;
                    $acres = $produto->adicionais->where('tipo', \App\Models\Adicional::TIPO_ACRESCENTAR);
                    $temAcrescimo = $produto->permite_adicionais && $acres->isNotEmpty();
                    $maxRet = $produto->limiteRetiradaIngredientesNaLoja();
                    $temRetirarIng = $produto->ingredientes->isNotEmpty() && $maxRet > 0;
                    $temPersonalizar = ($produto->permite_adicionais && $acres->isNotEmpty()) || $temRetirarIng;
                    $colEscolhas = Schema::hasColumn('produtos', 'acrescimo_escolhas_min');
                    $minEsc = $colEscolhas ? $produto->acrescimo_escolhas_min : null;
                    $maxEsc = $colEscolhas ? $produto->acrescimo_escolhas_max : null;
                    $temLimiteAcrescimo = $produto->permite_adicionais && $acres->isNotEmpty() && ($minEsc !== null || $maxEsc !== null);
                    $uiRetirarIng = $produto->modoRetirarIngredientesNaLoja();
                    $uiAcrescimos = $produto->modoAcrescimosNaLoja();
                @endphp
                <p class="h4 text-success mb-1">R$ {{ number_format((float) $produto->preco, 2, ',', '.') }}</p>
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
                    @if ($errors->any())
                        <div class="alert alert-danger small mb-3">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form id="vf-form-carrinho-produto" action="{{ route('publico.carrinho.adicionar', ['slug' => $slug]) }}" method="post" class="mb-4 vf-form-carrinho-produto">
                        @csrf
                        <input type="hidden" name="produto_id" value="{{ $produto->id }}">

                        @if ($temPersonalizar)
                            <div class="vf-card p-2 p-md-3 mb-3 vf-card-personalizar-produto">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-3 flex-wrap">
                                    <h2 class="h6 fw-bold mb-0">Personalizar</h2>
                                </div>

                                @if ($produto->permite_adicionais && $acres->isNotEmpty())
                                    @if ($temLimiteAcrescimo && $minEsc !== null && $maxEsc !== null && (int) $minEsc === (int) $maxEsc)
                                        <p class="fw-semibold mb-2 d-flex align-items-center gap-2 flex-wrap">
                                            <span>Escolha {{ (int) $minEsc }} opções</span>
                                            <span class="vf-personalizar-limite-chip text-muted">Mínimo: {{ (int) $minEsc }} · Máximo: {{ (int) $maxEsc }}</span>
                                        </p>
                                    @else
                                        <p class="fw-semibold mb-2">Opções</p>
                                    @endif
                                    @if ($uiAcrescimos === \App\Models\Produto::ACRESCIMO_LOJA_UI_CHECKBOX)
                                        <div class="vf-personalizar-grid vf-retirar-checkbox-grid vf-acrescimo-checkbox-grid mb-1"
                                            id="vf-acrescimos-checkbox"
                                            data-usa-limite="{{ $temLimiteAcrescimo ? '1' : '0' }}"
                                            data-min="{{ $temLimiteAcrescimo && $minEsc !== null ? (int) $minEsc : 0 }}"
                                            data-max="{{ $temLimiteAcrescimo && $maxEsc !== null ? (int) $maxEsc : 99999 }}">
                                            @foreach ($acres as $ad)
                                                <div class="vf-escolha-card vf-escolha-card--acrescimo-chk" data-ad-id="{{ $ad->id }}">
                                                    <div class="vf-escolha-card-inner vf-escolha-card-inner--retirar-chk">
                                                        <span class="vf-escolha-bar" aria-hidden="true"></span>
                                                        <div class="vf-retirar-chk-wrap flex-grow-1 min-w-0 d-flex align-items-start gap-3 py-2 ps-2 pe-3">
                                                            <input class="form-check-input vf-acrescimo-chk flex-shrink-0 mt-1" type="checkbox" id="acre_ad_{{ $ad->id }}" autocomplete="off" aria-describedby="acre_ad_label_{{ $ad->id }}" @checked(((int) old('adicional_qtd.'.$ad->id, 0)) > 0)>
                                                            <label class="form-check-label mb-0 flex-grow-1" for="acre_ad_{{ $ad->id }}" id="acre_ad_label_{{ $ad->id }}">
                                                                {{ $ad->nome }}
                                                                @if ((float) $ad->preco > 0)
                                                                    <span class="d-block small text-success fw-normal mt-1">+ R$ {{ number_format((float) $ad->preco, 2, ',', '.') }} @if ($temLimiteAcrescimo)<span class="text-muted">cada</span>@endif</span>
                                                                @endif
                                                            </label>
                                                            <input type="hidden" name="adicional_qtd[{{ $ad->id }}]" value="{{ (int) old('adicional_qtd.'.$ad->id, 0) }}" class="vf-acrescimo-qty-input" autocomplete="off">
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="vf-personalizar-grid vf-acrescimo-stepper-grid mb-1"
                                            id="vf-acrescimos-stepper"
                                            data-usa-limite="{{ $temLimiteAcrescimo ? '1' : '0' }}"
                                            data-min="{{ $temLimiteAcrescimo && $minEsc !== null ? (int) $minEsc : 0 }}"
                                            data-max="{{ $temLimiteAcrescimo && $maxEsc !== null ? (int) $maxEsc : 99999 }}">
                                            @foreach ($acres as $ad)
                                                <div class="vf-escolha-card" data-ad-id="{{ $ad->id }}">
                                                    <div class="vf-escolha-card-inner">
                                                        <span class="vf-escolha-bar" aria-hidden="true"></span>
                                                        <div class="vf-escolha-textos">
                                                            <span class="vf-personalizar-nome">
                                                                {{ $ad->nome }}
                                                                @if ((float) $ad->preco > 0)
                                                                    <span class="d-block small text-success fw-normal mt-1">+ R$ {{ number_format((float) $ad->preco, 2, ',', '.') }} @if ($temLimiteAcrescimo)<span class="text-muted">cada</span>@endif</span>
                                                                @endif
                                                            </span>
                                                            <span class="vf-escolha-badge"><i class="bi bi-check-lg me-1"></i>Selecionado</span>
                                                        </div>
                                                        <div class="vf-escolha-stepper" role="group" aria-label="Quantidade {{ $ad->nome }}">
                                                            <button type="button" class="vf-escolha-btn vf-escolha-btn--menos" aria-label="Diminuir quantidade">−</button>
                                                            <span class="vf-escolha-qty-wrap"><span class="vf-escolha-qty-disp" aria-live="polite">0</span></span>
                                                            <input type="hidden" name="adicional_qtd[{{ $ad->id }}]" value="{{ (int) old('adicional_qtd.'.$ad->id, 0) }}" class="vf-acrescimo-qty-input" autocomplete="off">
                                                            <button type="button" class="vf-escolha-btn vf-escolha-btn--mais" aria-label="Aumentar quantidade">+</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                @endif

                                @if ($temRetirarIng)
                                    @if ($uiRetirarIng === \App\Models\Produto::ING_RETIRAR_UI_CHECKBOX)
                                        <p class="fw-semibold mb-2 d-flex align-items-center gap-2 flex-wrap {{ $produto->permite_adicionais && $acres->isNotEmpty() ? 'mt-3' : '' }}">
                                            <span>Escolha {{ $maxRet }} {{ (int) $maxRet === 1 ? 'opção' : 'opções' }}</span>
                                            <span class="vf-personalizar-limite-chip text-muted">Mínimo: {{ $maxRet }} · Máximo: {{ $maxRet }}</span>
                                        </p>
                                        <div class="vf-personalizar-grid vf-retirar-checkbox-grid mb-1 {{ $produto->permite_adicionais && $acres->isNotEmpty() ? 'mt-1' : '' }}"
                                            id="vf-retirar-checkbox"
                                            data-min-total="{{ $maxRet }}"
                                            data-max-total="{{ $maxRet }}">
                                            @foreach ($produto->ingredientes as $ing)
                                                <div class="vf-escolha-card vf-escolha-card--retirar" data-ing-id="{{ $ing->id }}">
                                                    <div class="vf-escolha-card-inner vf-escolha-card-inner--retirar-chk">
                                                        <span class="vf-escolha-bar" aria-hidden="true"></span>
                                                        @if ($ing->urlFoto())
                                                            <div class="vf-escolha-thumb-wrap flex-shrink-0" aria-hidden="true">
                                                                <img src="{{ $ing->urlFoto() }}" alt="" class="vf-escolha-thumb-ing rounded border" width="44" height="44">
                                                            </div>
                                                        @endif
                                                        <div class="vf-retirar-chk-wrap flex-grow-1 min-w-0 d-flex align-items-start gap-3 py-2 ps-2 pe-3">
                                                            <input class="form-check-input vf-retirar-chk flex-shrink-0 mt-1" type="checkbox" id="ret_ing_{{ $ing->id }}" autocomplete="off" aria-describedby="ret_ing_label_{{ $ing->id }}" @checked(((int) old('retirar_qtd.'.$ing->id, 0)) > 0)>
                                                            <label class="form-check-label mb-0 flex-grow-1" for="ret_ing_{{ $ing->id }}" id="ret_ing_label_{{ $ing->id }}">{{ $ing->nome }}</label>
                                                            <input type="hidden" name="retirar_qtd[{{ $ing->id }}]" value="{{ (int) old('retirar_qtd.'.$ing->id, 0) }}" class="vf-retirar-qty-input" autocomplete="off">
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="fw-semibold mb-2 d-flex align-items-center gap-2 flex-wrap {{ $produto->permite_adicionais && $acres->isNotEmpty() ? 'mt-3' : '' }}">
                                            <span>Escolha {{ $maxRet }} {{ (int) $maxRet === 1 ? 'opção' : 'opções' }}</span>
                                            <span class="vf-personalizar-limite-chip text-muted">Mínimo: {{ $maxRet }} · Máximo: {{ $maxRet }}</span>
                                        </p>
                                        <div class="vf-personalizar-grid vf-acrescimo-stepper-grid vf-retirar-stepper-grid mb-1 {{ $produto->permite_adicionais && $acres->isNotEmpty() ? 'mt-1' : '' }}"
                                            id="vf-retirar-stepper"
                                            data-min-total="{{ $maxRet }}"
                                            data-max-total="{{ $maxRet }}">
                                            @foreach ($produto->ingredientes as $ing)
                                                <div class="vf-escolha-card vf-escolha-card--retirar" data-ing-id="{{ $ing->id }}">
                                                    <div class="vf-escolha-card-inner">
                                                        <span class="vf-escolha-bar" aria-hidden="true"></span>
                                                        @if ($ing->urlFoto())
                                                            <div class="vf-escolha-thumb-wrap flex-shrink-0" aria-hidden="true">
                                                                <img src="{{ $ing->urlFoto() }}" alt="" class="vf-escolha-thumb-ing rounded border" width="40" height="40">
                                                            </div>
                                                        @endif
                                                        <div class="vf-escolha-textos">
                                                            <span class="vf-personalizar-nome">{{ $ing->nome }}</span>
                                                            <span class="vf-escolha-badge vf-escolha-badge--retirar"><i class="bi bi-check-lg me-1"></i>Selecionado</span>
                                                        </div>
                                                        <div class="vf-escolha-stepper" role="group" aria-label="Opção {{ $ing->nome }}">
                                                            <button type="button" class="vf-escolha-btn vf-escolha-btn--menos" aria-label="Diminuir">−</button>
                                                            <span class="vf-escolha-qty-wrap"><span class="vf-escolha-qty-disp" aria-live="polite">0</span></span>
                                                            <input type="hidden" name="retirar_qtd[{{ $ing->id }}]" value="{{ (int) old('retirar_qtd.'.$ing->id, 0) }}" class="vf-retirar-qty-input" autocomplete="off">
                                                            <button type="button" class="vf-escolha-btn vf-escolha-btn--mais" aria-label="Aumentar">+</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @endif

                        <div class="mb-3 {{ ($temPersonalizar ?? false) ? 'mt-1' : 'mt-2' }}">
                            <label class="form-label small text-muted mb-1" for="observacao_produto">Observação <span class="fw-normal">(opcional)</span></label>
                            <textarea
                                class="form-control @error('observacao') is-invalid @enderror"
                                name="observacao"
                                id="observacao_produto"
                                rows="5"
                                maxlength="500"
                                aria-describedby="observacao_limite_ajuda"
                                placeholder="Ex.: ponto da carne, sem cebola, embalar separado…">{{ old('observacao') }}</textarea>
                            @error('observacao')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div id="observacao_limite_ajuda" class="form-text mt-1">
                                <span class="text-muted"><span id="observacao-count">0</span>/500 caracteres · aparece no pedido</span>
                            </div>
                        </div>

                        @php
                            $vfWaLojaRaw = trim((string) ($empresa->whatsapp ?? ''));
                            $vfWaLojaDig = $vfWaLojaRaw !== '' ? preg_replace('/\D+/', '', $vfWaLojaRaw) : '';
                            if ($vfWaLojaDig !== '' && (strlen($vfWaLojaDig) === 10 || strlen($vfWaLojaDig) === 11)) {
                                $vfWaLojaDig = '55'.$vfWaLojaDig;
                            }
                            $vfWaLojaHref = $vfWaLojaDig !== ''
                                ? 'https://wa.me/'.$vfWaLojaDig.'?text='.rawurlencode('Olá! Vim pela vitrine e estou vendo o produto '.$produto->nome.'. ')
                                : '';
                        @endphp
                        <div class="vf-produto-barra-acoes d-flex flex-column gap-3">
                            <div>
                                <label class="form-label small text-muted mb-1" for="qtd">Quantidade</label>
                                <input type="number" class="form-control" id="qtd" name="quantidade" value="1" min="1" max="{{ $produto->estoque !== null ? min(99, $produto->estoque) : 99 }}" style="max-width: 5rem;">
                            </div>
                            <div class="d-flex flex-row gap-2 align-items-stretch">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <i class="bi bi-cart-plus me-1"></i>Adicionar ao carrinho
                                </button>
                                @if ($vfWaLojaHref !== '')
                                    <a href="{{ $vfWaLojaHref }}" target="_blank" rel="noopener noreferrer" class="btn btn-success vf-produto-wa-loja-btn flex-fill">
                                        <i class="bi bi-whatsapp me-1" aria-hidden="true"></i><span class="d-none d-sm-inline">WhatsApp da loja</span><span class="d-sm-none">WhatsApp</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                @endif

                <div class="small text-muted mb-4 pb-2"><a href="{{ route('publico.loja', ['slug' => $slug]) }}"><i class="bi bi-arrow-left me-1"></i>Voltar ao cardápio</a></div>
            </div>
        </div>
    </div>
    @if ($produto->estoque === null || $produto->estoque > 0)
        @push('scripts')
            <script>
                (function () {
                    var ta = document.getElementById('observacao_produto');
                    if (ta) {
                        var countEl = document.getElementById('observacao-count');
                        function syncObservacaoContagem() {
                            if (countEl) {
                                countEl.textContent = String(ta.value.length);
                            }
                        }
                        ta.addEventListener('input', syncObservacaoContagem);
                        syncObservacaoContagem();
                    }

                    var hid = document.getElementById('vf_nota_produto');
                    var btns = document.querySelectorAll('.vf-estrela-produto-btn');
                    if (hid && btns.length) {
                        function paintEstrelas(valor) {
                            var n = parseInt(String(valor || ''), 10);
                            if (isNaN(n) || n < 1 || n > 5) {
                                n = 0;
                            }
                            btns.forEach(function (btn, i) {
                                var alvo = i + 1;
                                var ico = btn.querySelector('.vf-estrela-produto-ico');
                                var ligado = n >= 1 && alvo <= n;
                                if (ico) {
                                    ico.classList.toggle('bi-star-fill', ligado);
                                    ico.classList.toggle('bi-star', !ligado);
                                }
                            });
                        }

                        btns.forEach(function (btn) {
                            btn.addEventListener('click', function () {
                                var val = parseInt(btn.getAttribute('data-vf-estrela'), 10);
                                var cur = parseInt(String(hid.value || ''), 10);
                                if (!isNaN(cur) && cur === val) {
                                    hid.value = cur <= 1 ? '' : String(cur - 1);
                                } else {
                                    hid.value = String(val);
                                }
                                paintEstrelas(hid.value);
                            });
                        });
                        paintEstrelas(hid.value);
                    }
                })();
            </script>
        @endpush
    @endif
    @if ($temPersonalizar ?? false)
        @push('scripts')
            <script>
                (function () {
                    var form = document.querySelector('form.vf-form-carrinho-produto');
                    if (!form) {
                        form = document.querySelector('form[action*="carrinho.adicionar"]');
                    }
                    if (!form) return;

                    form.addEventListener('submit', function () {
                        var wc = document.getElementById('vf-retirar-checkbox');
                        if (wc) {
                            wc.querySelectorAll('.vf-escolha-card--retirar').forEach(function (card) {
                                var chk = card.querySelector('.vf-retirar-chk');
                                var hid = card.querySelector('.vf-retirar-qty-input');
                                if (chk && hid) {
                                    hid.value = chk.checked ? '1' : '0';
                                }
                            });
                        }
                        var wAcreChk = document.getElementById('vf-acrescimos-checkbox');
                        if (wAcreChk) {
                            wAcreChk.querySelectorAll('.vf-escolha-card--acrescimo-chk').forEach(function (card) {
                                var chkA = card.querySelector('.vf-acrescimo-chk');
                                var hidA = card.querySelector('.vf-acrescimo-qty-input');
                                if (chkA && hidA) {
                                    hidA.value = chkA.checked ? '1' : '0';
                                }
                            });
                        }
                        form.querySelectorAll('.vf-acrescimo-qty-input, .vf-retirar-qty-input').forEach(function (inp) {
                            var n = parseInt(inp.value || '0', 10);
                            if (isNaN(n)) {
                                n = 0;
                            }
                            n = Math.max(0, Math.min(999, n));
                            inp.value = String(n);
                            inp.disabled = false;
                        });
                    });

                    var wrap = document.getElementById('vf-acrescimos-stepper');
                    if (wrap) {
                        var usaLimite = wrap.getAttribute('data-usa-limite') === '1';
                        var min = parseInt(wrap.getAttribute('data-min') || '0', 10);
                        var max = parseInt(wrap.getAttribute('data-max') || '99999', 10);
                        var maxPorOpcao = 999;

                        function soma() {
                            var t = 0;
                            wrap.querySelectorAll('.vf-acrescimo-qty-input').forEach(function (inp) {
                                t += parseInt(inp.value || '0', 10) || 0;
                            });
                            return t;
                        }

                        function atualizarCard(card) {
                            var inp = card.querySelector('.vf-acrescimo-qty-input');
                            var q = parseInt(inp.value || '0', 10) || 0;
                            var disp = card.querySelector('.vf-escolha-qty-disp');
                            var btnMais = card.querySelector('.vf-escolha-btn--mais');
                            var btnMenos = card.querySelector('.vf-escolha-btn--menos');
                            if (disp) disp.textContent = String(q);
                            card.classList.toggle('vf-escolha-card--ativo', q > 0);
                            if (btnMenos) btnMenos.disabled = q < 1;
                            var total = soma();
                            var podeMais = q < maxPorOpcao;
                            if (usaLimite && podeMais && total >= max) {
                                podeMais = false;
                            }
                            if (btnMais) btnMais.disabled = !podeMais;
                        }

                        wrap.querySelectorAll('.vf-escolha-card').forEach(function (card) {
                            var inp = card.querySelector('.vf-acrescimo-qty-input');
                            var btnMais = card.querySelector('.vf-escolha-btn--mais');
                            var btnMenos = card.querySelector('.vf-escolha-btn--menos');

                            function setQ(novo) {
                                var q = Math.max(0, parseInt(novo, 10) || 0);
                                q = Math.min(q, maxPorOpcao);
                                inp.value = String(q);
                                wrap.querySelectorAll('.vf-escolha-card').forEach(atualizarCard);
                            }

                            function tentarMais() {
                                var q = parseInt(inp.value || '0', 10) || 0;
                                if (q >= maxPorOpcao) return;
                                if (usaLimite && soma() >= max) {
                                    alert('Você já atingiu o máximo de ' + max + ' opções (somando as quantidades).');
                                    return;
                                }
                                setQ(q + 1);
                            }

                            if (btnMais) {
                                btnMais.addEventListener('click', tentarMais);
                            }
                            if (btnMenos) {
                                btnMenos.addEventListener('click', function () {
                                    var q = parseInt(inp.value || '0', 10) || 0;
                                    setQ(q - 1);
                                });
                            }
                            atualizarCard(card);
                        });

                        form.addEventListener('submit', function (e) {
                            if (!usaLimite) return;
                            var s = soma();
                            if (s < min || s > max) {
                                e.preventDefault();
                                alert('Escolha entre ' + min + ' e ' + max + ' opções de acréscimo (somando as quantidades).');
                                return false;
                            }
                        });
                    }

                    var wrapAcreChk = document.getElementById('vf-acrescimos-checkbox');
                    if (wrapAcreChk) {
                        var usaLimiteA = wrapAcreChk.getAttribute('data-usa-limite') === '1';
                        var minA = parseInt(wrapAcreChk.getAttribute('data-min') || '0', 10);
                        var maxA = parseInt(wrapAcreChk.getAttribute('data-max') || '99999', 10);

                        function somaAcreChk() {
                            var t = 0;
                            wrapAcreChk.querySelectorAll('.vf-acrescimo-qty-input').forEach(function (inp) {
                                t += parseInt(inp.value || '0', 10) || 0;
                            });
                            return t;
                        }

                        wrapAcreChk.querySelectorAll('.vf-escolha-card--acrescimo-chk').forEach(function (card) {
                            var chk = card.querySelector('.vf-acrescimo-chk');
                            var hid = card.querySelector('.vf-acrescimo-qty-input');
                            if (!chk || !hid) return;
                            function sync() {
                                hid.value = chk.checked ? '1' : '0';
                                card.classList.toggle('vf-escolha-card--ativo', chk.checked);
                            }
                            chk.addEventListener('change', function () {
                                if (chk.checked && usaLimiteA) {
                                    var outros = 0;
                                    wrapAcreChk.querySelectorAll('.vf-acrescimo-qty-input').forEach(function (h) {
                                        if (h !== hid) outros += parseInt(h.value || '0', 10) || 0;
                                    });
                                    if (outros + 1 > maxA) {
                                        chk.checked = false;
                                        alert('Você já atingiu o máximo de ' + maxA + ' opções (somando as quantidades).');
                                    }
                                }
                                sync();
                            });
                            sync();
                        });

                        form.addEventListener('submit', function (e) {
                            wrapAcreChk.querySelectorAll('.vf-escolha-card--acrescimo-chk').forEach(function (card) {
                                var ch = card.querySelector('.vf-acrescimo-chk');
                                var hi = card.querySelector('.vf-acrescimo-qty-input');
                                if (ch && hi) hi.value = ch.checked ? '1' : '0';
                            });
                            if (!usaLimiteA) return;
                            var s = somaAcreChk();
                            if (s < minA || s > maxA) {
                                e.preventDefault();
                                alert('Escolha entre ' + minA + ' e ' + maxA + ' opções de acréscimo (somando as quantidades).');
                                return false;
                            }
                        });
                    }

                    var wrapRet = document.getElementById('vf-retirar-stepper');
                    if (wrapRet) {
                        var minTotalRet = parseInt(wrapRet.getAttribute('data-min-total') || '0', 10);
                        var maxTotalRet = parseInt(wrapRet.getAttribute('data-max-total') || '0', 10);
                        var maxPorLinha = 999;

                        function somaRet() {
                            var t = 0;
                            wrapRet.querySelectorAll('.vf-retirar-qty-input').forEach(function (inp) {
                                t += parseInt(inp.value || '0', 10) || 0;
                            });
                            return t;
                        }

                        function atualizarCardRet(card) {
                            var inp = card.querySelector('.vf-retirar-qty-input');
                            var q = parseInt(inp.value || '0', 10) || 0;
                            var disp = card.querySelector('.vf-escolha-qty-disp');
                            var btnMais = card.querySelector('.vf-escolha-btn--mais');
                            var btnMenos = card.querySelector('.vf-escolha-btn--menos');
                            if (disp) disp.textContent = String(q);
                            card.classList.toggle('vf-escolha-card--ativo', q > 0);
                            if (btnMenos) btnMenos.disabled = q < 1;
                            var total = somaRet();
                            var podeMais = q < maxPorLinha && total < maxTotalRet;
                            if (btnMais) btnMais.disabled = !podeMais;
                        }

                        wrapRet.querySelectorAll('.vf-escolha-card--retirar').forEach(function (card) {
                            var inp = card.querySelector('.vf-retirar-qty-input');
                            var btnMais = card.querySelector('.vf-escolha-btn--mais');
                            var btnMenos = card.querySelector('.vf-escolha-btn--menos');

                            function setQ(novo) {
                                var q = Math.max(0, parseInt(novo, 10) || 0);
                                q = Math.min(q, maxPorLinha);
                                inp.value = String(q);
                                wrapRet.querySelectorAll('.vf-escolha-card--retirar').forEach(atualizarCardRet);
                            }

                            function tentarMaisRet() {
                                var q = parseInt(inp.value || '0', 10) || 0;
                                if (q >= maxPorLinha) return;
                                if (somaRet() >= maxTotalRet) {
                                    alert('Você pode escolher no máximo ' + maxTotalRet + ' (somando as quantidades).');
                                    return;
                                }
                                setQ(q + 1);
                            }

                            if (btnMais) btnMais.addEventListener('click', tentarMaisRet);
                            if (btnMenos) {
                                btnMenos.addEventListener('click', function () {
                                    var q = parseInt(inp.value || '0', 10) || 0;
                                    setQ(q - 1);
                                });
                            }
                            atualizarCardRet(card);
                        });

                        form.addEventListener('submit', function (e) {
                            var s = somaRet();
                            if (s < minTotalRet || s > maxTotalRet) {
                                e.preventDefault();
                                alert('Escolha entre ' + minTotalRet + ' e ' + maxTotalRet + ' ingrediente(s) para retirar (somando as quantidades).');
                                return false;
                            }
                        });
                    }

                    var wrapChk = document.getElementById('vf-retirar-checkbox');
                    if (wrapChk) {
                        var minChk = parseInt(wrapChk.getAttribute('data-min-total') || '0', 10);
                        var maxChk = parseInt(wrapChk.getAttribute('data-max-total') || '0', 10);
                        function somaChk() {
                            var t = 0;
                            wrapChk.querySelectorAll('.vf-retirar-qty-input').forEach(function (inp) {
                                t += parseInt(inp.value || '0', 10) || 0;
                            });
                            return t;
                        }
                        wrapChk.querySelectorAll('.vf-escolha-card--retirar').forEach(function (card) {
                            var chk = card.querySelector('.vf-retirar-chk');
                            var hid = card.querySelector('.vf-retirar-qty-input');
                            if (!chk || !hid) return;
                            function sync() {
                                hid.value = chk.checked ? '1' : '0';
                                card.classList.toggle('vf-escolha-card--ativo', chk.checked);
                            }
                            chk.addEventListener('change', function () {
                                if (chk.checked) {
                                    var outros = 0;
                                    wrapChk.querySelectorAll('.vf-retirar-qty-input').forEach(function (h) {
                                        if (h !== hid) outros += parseInt(h.value || '0', 10) || 0;
                                    });
                                    if (outros + 1 > maxChk) {
                                        chk.checked = false;
                                        alert('Você pode marcar no máximo ' + maxChk + ' ingrediente(s) para retirar.');
                                    }
                                }
                                sync();
                            });
                            sync();
                        });

                        form.addEventListener('submit', function (e) {
                            var s = somaChk();
                            if (s < minChk || s > maxChk) {
                                e.preventDefault();
                                alert('Escolha entre ' + minChk + ' e ' + maxChk + ' ingrediente(s) para retirar.');
                                return false;
                            }
                        });
                    }
                })();
            </script>
        @endpush
    @endif
    @push('scripts')
        <script>
            (function () {
                var btn = document.querySelector('.vf-share-instagram');
                if (!btn) return;
                btn.addEventListener('click', function () {
                    var u = btn.getAttribute('data-share-url');
                    if (!u) return;
                    function feedback() {
                        var prevTitle = btn.getAttribute('title') || '';
                        btn.setAttribute('title', 'Link copiado! Cole no Instagram.');
                        setTimeout(function () {
                            btn.setAttribute('title', prevTitle);
                        }, 2500);
                        var sp = btn.querySelector('.vf-share-instagram-label');
                        if (sp) {
                            var t = sp.textContent;
                            sp.textContent = 'Copiado!';
                            setTimeout(function () {
                                sp.textContent = t;
                            }, 2000);
                        }
                    }
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(u).then(feedback).catch(function () {
                            window.prompt('Copie o link do produto:', u);
                        });
                    } else {
                        window.prompt('Copie o link do produto:', u);
                    }
                });
            })();
        </script>
    @endpush
@endsection
