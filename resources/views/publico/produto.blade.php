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
                    use Illuminate\Support\Facades\Schema;
                    $acres = $produto->adicionais->where('tipo', \App\Models\Adicional::TIPO_ACRESCENTAR);
                    $temAcrescimo = $produto->permite_adicionais && $acres->isNotEmpty();
                    $maxRet = (int) ($produto->max_ingredientes_retirar ?? 0);
                    $temRetirarIng = $produto->ingredientes->isNotEmpty() && $maxRet > 0;
                    $temPersonalizar = ($produto->permite_adicionais && $acres->isNotEmpty()) || $temRetirarIng;
                    $colEscolhas = Schema::hasColumn('produtos', 'acrescimo_escolhas_min');
                    $minEsc = $colEscolhas ? $produto->acrescimo_escolhas_min : null;
                    $maxEsc = $colEscolhas ? $produto->acrescimo_escolhas_max : null;
                    $temLimiteAcrescimo = $produto->permite_adicionais && $acres->isNotEmpty() && ($minEsc !== null || $maxEsc !== null);
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
                    <form action="{{ route('publico.carrinho.adicionar', ['slug' => $slug]) }}" method="post" class="mb-4 vf-form-carrinho-produto">
                        @csrf
                        <input type="hidden" name="produto_id" value="{{ $produto->id }}">

                        @if ($temPersonalizar)
                            @php
                                $limitesNoCard = [];
                                if ($temLimiteAcrescimo && ($minEsc !== null || $maxEsc !== null)) {
                                    $limitesNoCard[] = 'Opções — mín. '.($minEsc ?? '—').' · máx. '.($maxEsc ?? '—');
                                }
                                if ($temRetirarIng) {
                                    $limitesNoCard[] = 'Ingredientes — mín. 0 · máx. '.$maxRet;
                                }
                            @endphp
                            <div class="vf-card p-3 mb-3 vf-card-personalizar-produto">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-3 flex-wrap">
                                    <h2 class="h6 fw-bold mb-0">Personalizar</h2>
                                    @if ($limitesNoCard !== [])
                                        <div class="vf-personalizar-limites-no-card small text-muted ms-md-auto">
                                            @foreach ($limitesNoCard as $txtLimite)
                                                <span class="vf-personalizar-limite-chip d-inline-block">{{ $txtLimite }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                @if ($produto->permite_adicionais && $acres->isNotEmpty())
                                    @if ($temLimiteAcrescimo && $minEsc !== null && $maxEsc !== null && (int) $minEsc === (int) $maxEsc)
                                        <p class="fw-semibold mb-2">Escolha {{ $minEsc }} opções</p>
                                    @else
                                        <p class="fw-semibold mb-2">Opções</p>
                                    @endif
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
                                                        <input type="hidden" name="adicional_qtd[{{ $ad->id }}]" value="0" class="vf-acrescimo-qty-input" autocomplete="off">
                                                        <button type="button" class="vf-escolha-btn vf-escolha-btn--mais" aria-label="Aumentar quantidade">+</button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($temRetirarIng)
                                    <div class="vf-personalizar-grid vf-acrescimo-stepper-grid vf-retirar-stepper-grid mb-1 {{ $produto->permite_adicionais && $acres->isNotEmpty() ? 'mt-3' : '' }}"
                                        id="vf-retirar-stepper"
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
                                                        <input type="hidden" name="retirar_qtd[{{ $ing->id }}]" value="0" class="vf-retirar-qty-input" autocomplete="off">
                                                        <button type="button" class="vf-escolha-btn vf-escolha-btn--mais" aria-label="Aumentar">+</button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
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
    @if (($temRetirarIng ?? false) || ($temAcrescimo ?? false))
        @push('scripts')
            <script>
                (function () {
                    var form = document.querySelector('form.vf-form-carrinho-produto');
                    if (!form) {
                        form = document.querySelector('form[action*="carrinho.adicionar"]');
                    }
                    if (!form) return;

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

                    var wrapRet = document.getElementById('vf-retirar-stepper');
                    if (wrapRet) {
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
                    }
                })();
            </script>
        @endpush
    @endif
@endsection
