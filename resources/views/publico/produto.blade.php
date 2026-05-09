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
                                <h2 class="h6 fw-bold mb-3">Personalizar</h2>
                                @if ($temRetirarIng)
                                    <p class="small text-muted mb-3 border-start border-3 border-secondary-subtle ps-2">Retirar ingredientes é opcional e <strong>não reduz</strong> o valor do produto.</p>
                                @endif

                                @if ($produto->permite_adicionais && $acres->isNotEmpty())
                                    @if ($temLimiteAcrescimo)
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2 flex-wrap">
                                            <span class="fw-semibold">
                                                @if ($minEsc !== null && $maxEsc !== null && (int) $minEsc === (int) $maxEsc)
                                                    Escolha {{ $minEsc }} opções
                                                @else
                                                    Acrescentar
                                                @endif
                                            </span>
                                            <span class="small text-muted text-end">Mínimo: {{ $minEsc ?? '—' }} e Máximo: {{ $maxEsc ?? '—' }}</span>
                                        </div>
                                        <div class="vf-personalizar-grid vf-acrescimo-limite-grid mb-1"
                                            id="vf-acrescimos-limite"
                                            data-min="{{ $minEsc !== null ? (int) $minEsc : 0 }}"
                                            data-max="{{ $maxEsc !== null ? (int) $maxEsc : 99999 }}">
                                            @foreach ($acres as $ad)
                                                <div class="vf-escolha-card" data-ad-id="{{ $ad->id }}">
                                                    <div class="vf-escolha-card-inner">
                                                        <span class="vf-escolha-bar" aria-hidden="true"></span>
                                                        <div class="vf-escolha-textos">
                                                            <span class="vf-personalizar-nome">
                                                                {{ $ad->nome }}
                                                                @if ((float) $ad->preco > 0)
                                                                    <span class="d-block small text-success fw-normal mt-1">+ R$ {{ number_format((float) $ad->preco, 2, ',', '.') }} <span class="text-muted">cada</span></span>
                                                                @endif
                                                            </span>
                                                            <span class="vf-escolha-badge"><i class="bi bi-check-lg me-1"></i>Selecionado</span>
                                                        </div>
                                                        <div class="vf-escolha-stepper" hidden>
                                                            <button type="button" class="vf-escolha-btn vf-escolha-btn--menos" aria-label="Diminuir">−</button>
                                                            <span class="vf-escolha-qty-disp" aria-live="polite">0</span>
                                                            <input type="hidden" name="adicional_qtd[{{ $ad->id }}]" value="0" class="vf-acrescimo-qty-input" autocomplete="off">
                                                            <button type="button" class="vf-escolha-btn vf-escolha-btn--mais" aria-label="Aumentar">+</button>
                                                        </div>
                                                        <button type="button" class="vf-escolha-solo-mais" aria-label="Adicionar opção"><span class="vf-escolha-solo-mais-ico">+</span></button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="d-flex justify-content-between align-items-baseline gap-2 mb-2">
                                            <span class="fw-semibold">Acrescentar</span>
                                            <span class="small text-muted">Opcional</span>
                                        </div>
                                        <div class="vf-personalizar-grid mb-1">
                                            @foreach ($acres as $ad)
                                                <label class="vf-personalizar-card vf-personalizar-card--adicional">
                                                    <input class="vf-personalizar-input visually-hidden" type="checkbox" name="adicional_ids[]" id="adicional_{{ $ad->id }}" value="{{ $ad->id }}">
                                                    <span class="vf-personalizar-nome">
                                                        {{ $ad->nome }}
                                                        @if ((float) $ad->preco > 0)
                                                            <span class="d-block small text-success fw-normal mt-1">+ R$ {{ number_format((float) $ad->preco, 2, ',', '.') }}</span>
                                                        @endif
                                                    </span>
                                                    <span class="vf-personalizar-btn vf-personalizar-btn--add" aria-hidden="true">
                                                        <i class="bi bi-plus-lg vf-personalizar-ico-on"></i>
                                                        <i class="bi bi-check-lg vf-personalizar-ico-off"></i>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                @endif

                                @if ($temRetirarIng)
                                    <div class="d-flex justify-content-between align-items-baseline gap-2 mb-2 mt-3">
                                        <span class="fw-semibold">Retirar ingrediente</span>
                                        <span class="small text-muted text-end">Mínimo: 0 · Máximo: {{ $maxRet }}</span>
                                    </div>
                                    <div class="vf-personalizar-grid">
                                        @foreach ($produto->ingredientes as $ing)
                                            <label class="vf-personalizar-card vf-personalizar-card--retirar">
                                                <input class="vf-personalizar-input visually-hidden vf-retirar-ing" type="checkbox" name="retirar_ingrediente_ids[]" id="ing_{{ $ing->id }}" value="{{ $ing->id }}" data-max="{{ $maxRet }}">
                                                <span class="vf-personalizar-nome">Sem {{ $ing->nome }}</span>
                                                <span class="vf-personalizar-btn vf-personalizar-btn--retirar" aria-hidden="true">
                                                    <i class="bi bi-dash-lg vf-personalizar-ico-on"></i>
                                                    <i class="bi bi-check-lg vf-personalizar-ico-off"></i>
                                                </span>
                                            </label>
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
    @if (($temRetirarIng ?? false) || ($temLimiteAcrescimo ?? false))
        @push('scripts')
            <script>
                (function () {
                    const form = document.querySelector('form[action*="carrinho.adicionar"]');
                    if (!form) return;

                    const wrap = document.getElementById('vf-acrescimos-limite');
                    if (wrap) {
                        const min = parseInt(wrap.dataset.min || '0', 10);
                        const max = parseInt(wrap.dataset.max || '99999', 10);

                        function soma() {
                            let t = 0;
                            wrap.querySelectorAll('.vf-acrescimo-qty-input').forEach(function (inp) {
                                t += parseInt(inp.value || '0', 10) || 0;
                            });
                            return t;
                        }

                        function atualizarCard(card) {
                            const inp = card.querySelector('.vf-acrescimo-qty-input');
                            const q = parseInt(inp.value || '0', 10) || 0;
                            const step = card.querySelector('.vf-escolha-stepper');
                            const solo = card.querySelector('.vf-escolha-solo-mais');
                            const disp = card.querySelector('.vf-escolha-qty-disp');
                            if (disp) disp.textContent = String(q);
                            card.classList.toggle('vf-escolha-card--ativo', q > 0);
                            if (step) step.hidden = q < 1;
                            if (solo) solo.hidden = q > 0;
                        }

                        function refreshAll() {
                            wrap.querySelectorAll('.vf-escolha-card').forEach(atualizarCard);
                        }

                        wrap.querySelectorAll('.vf-escolha-card').forEach(function (card) {
                            const inp = card.querySelector('.vf-acrescimo-qty-input');
                            const btnMais = card.querySelector('.vf-escolha-btn--mais');
                            const btnMenos = card.querySelector('.vf-escolha-btn--menos');
                            const solo = card.querySelector('.vf-escolha-solo-mais');

                            function setQ(novo) {
                                const q = Math.max(0, parseInt(novo, 10) || 0);
                                inp.value = String(q);
                                atualizarCard(card);
                            }

                            if (solo) {
                                solo.addEventListener('click', function () {
                                    if (soma() >= max) {
                                        alert('Você já atingiu o máximo de ' + max + ' opções.');
                                        return;
                                    }
                                    setQ((parseInt(inp.value || '0', 10) || 0) + 1);
                                });
                            }
                            if (btnMais) {
                                btnMais.addEventListener('click', function () {
                                    if (soma() >= max) {
                                        alert('Você já atingiu o máximo de ' + max + ' opções.');
                                        return;
                                    }
                                    setQ((parseInt(inp.value || '0', 10) || 0) + 1);
                                });
                            }
                            if (btnMenos) {
                                btnMenos.addEventListener('click', function () {
                                    setQ((parseInt(inp.value || '0', 10) || 0) - 1);
                                });
                            }
                            atualizarCard(card);
                        });

                        form.addEventListener('submit', function (e) {
                            const s = soma();
                            if (s < min || s > max) {
                                e.preventDefault();
                                alert('Escolha entre ' + min + ' e ' + max + ' opções de acréscimo (somando as quantidades).');
                                return false;
                            }
                        });
                    }

                    const maxRet = parseInt(form.querySelector('.vf-retirar-ing')?.dataset.max || '0', 10);
                    if (maxRet > 0) {
                        form.addEventListener('change', function (e) {
                            if (!e.target.classList.contains('vf-retirar-ing')) return;
                            const checked = form.querySelectorAll('.vf-retirar-ing:checked');
                            if (checked.length > maxRet) {
                                e.target.checked = false;
                                alert('Você pode escolher no máximo ' + maxRet + ' ingrediente(s) para retirar.');
                            }
                        });
                    }
                })();
            </script>
        @endpush
    @endif
@endsection
