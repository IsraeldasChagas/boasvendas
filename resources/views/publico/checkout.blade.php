@extends('layouts.publico')

@section('title', 'Checkout — '.$empresa->nome)

@section('content')
    @php $modoFreteLoja = $empresa->lojaFreteModoEfetivo(); @endphp
    <div class="container">
        <h1 class="h4 fw-bold mb-3">Finalizar pedido</h1>
        <form action="{{ route('publico.checkout.finalizar', ['slug' => $slug]) }}" method="post">
            @csrf
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="vf-card p-3 mb-3">
                        <h2 class="h6 fw-bold mb-3">Seus dados e entrega</h2>
                        <div class="mb-3">
                            <span class="form-label small d-block mb-2">Como deseja receber</span>
                            <div class="form-check">
                                <input class="form-check-input vf-tipo-entrega" type="radio" name="tipo_entrega" id="tipo-entrega" value="{{ \App\Models\Pedido::TIPO_ENTREGA_ENTREGA }}" data-vf-entrega="1" @checked(old('tipo_entrega', $tipoCheckout) === \App\Models\Pedido::TIPO_ENTREGA_ENTREGA)>
                                <label class="form-check-label small" for="tipo-entrega">Entrega no endereço</label>
                            </div>
                            @if ($permiteBalcao)
                                <div class="form-check">
                                    <input class="form-check-input vf-tipo-entrega" type="radio" name="tipo_entrega" id="tipo-balcao" value="{{ \App\Models\Pedido::TIPO_ENTREGA_BALCAO }}" data-vf-entrega="0" @checked(old('tipo_entrega', $tipoCheckout) === \App\Models\Pedido::TIPO_ENTREGA_BALCAO)>
                                    <label class="form-check-label small" for="tipo-balcao">Retirada no balcão <span class="text-success">(sem taxa de entrega)</span></label>
                                </div>
                            @endif
                            @error('tipo_entrega')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="row g-2" id="vf-checkout-entrega-fields">
                            <div class="col-md-4">
                                <label class="form-label small" for="cep_entrega">CEP</label>
                                <input type="text" class="form-control @error('cep_entrega') is-invalid @enderror" id="cep_entrega" name="cep_entrega" value="{{ old('cep_entrega', $cepDigits !== '' ? substr($cepDigits, 0, 5).'-'.substr($cepDigits, 5) : '') }}" maxlength="9" placeholder="00000-000" autocomplete="postal-code">
                                @error('cep_entrega')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-8 d-flex align-items-end">
                                @if (\App\Models\Empresa::lojaFreteModoUsaKmRodoviario($modoFreteLoja))
                                    <p class="small text-muted mb-0">O frete usa a <strong>rota de carro</strong> entre a loja e o endereço informado.@if ($modoFreteLoja === \App\Models\Empresa::LOJA_FRETE_GOOGLE_DISTANCIA) Google Maps.@elseif ($modoFreteLoja === \App\Models\Empresa::LOJA_FRETE_OSRM_DISTANCIA) OpenStreetMap + OSRM.@endif No carrinho a simulação usa só o CEP; no pedido vale o endereço completo.</p>
                                @elseif ($modoFreteLoja === \App\Models\Empresa::LOJA_FRETE_PADRAO_UNICO)
                                    <p class="small text-muted mb-0">Esta loja usa <strong>taxa fixa</strong> de entrega (sem faixas de CEP).</p>
                                @else
                                    <p class="small text-muted mb-0">A taxa usa a <strong>faixa de CEP</strong> cadastrada pela loja; fora das faixas vale a taxa padrão.</p>
                                @endif
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small" for="endereco">Endereço de entrega</label>
                                <input type="text" class="form-control @error('endereco') is-invalid @enderror" id="endereco" name="endereco" value="{{ old('endereco') }}" maxlength="255" data-vf-entrega-req="1">
                                @error('endereco')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small" for="complemento">Complemento</label>
                                <input type="text" class="form-control @error('complemento') is-invalid @enderror" id="complemento" name="complemento" value="{{ old('complemento') }}" maxlength="120">
                                @error('complemento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            @if ($checkoutOsrm ?? false)
                                <div class="col-12" id="vf-checkout-osrm-extras">
                                    <p class="small text-muted mb-2">Detalhes do endereço <span class="text-muted">(melhoram o cálculo no mapa)</span></p>
                                    <div class="row g-2">
                                        <div class="col-md-3">
                                            <label class="form-label small" for="entrega_numero">Número</label>
                                            <input type="text" class="form-control form-control-sm" id="entrega_numero" name="entrega_numero" value="{{ old('entrega_numero') }}" maxlength="32" autocomplete="address-line2">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small" for="entrega_bairro">Bairro</label>
                                            <input type="text" class="form-control form-control-sm" id="entrega_bairro" name="entrega_bairro" value="{{ old('entrega_bairro') }}" maxlength="120">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small" for="entrega_cidade">Cidade</label>
                                            <input type="text" class="form-control form-control-sm" id="entrega_cidade" name="entrega_cidade" value="{{ old('entrega_cidade') }}" maxlength="120">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small" for="entrega_estado">UF</label>
                                            <input type="text" class="form-control form-control-sm text-uppercase" id="entrega_estado" name="entrega_estado" value="{{ old('entrega_estado') }}" maxlength="2" placeholder="RO">
                                        </div>
                                    </div>
                                    <p class="small text-muted mb-0 mt-2 d-none" id="vf-osrm-frete-meta"></p>
                                </div>
                            @endif
                        </div>
                        <hr class="my-3">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small" for="cliente_nome">Nome</label>
                                <input type="text" class="form-control @error('cliente_nome') is-invalid @enderror" id="cliente_nome" name="cliente_nome" value="{{ old('cliente_nome') }}" required maxlength="120">
                                @error('cliente_nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small" for="cliente_telefone">Telefone / WhatsApp</label>
                                <input type="text" class="form-control @error('cliente_telefone') is-invalid @enderror" id="cliente_telefone" name="cliente_telefone" value="{{ old('cliente_telefone') }}" required maxlength="32">
                                @error('cliente_telefone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label small" for="cliente_email">E-mail <span class="text-muted">(opcional)</span></label>
                                <input type="email" class="form-control @error('cliente_email') is-invalid @enderror" id="cliente_email" name="cliente_email" value="{{ old('cliente_email') }}" maxlength="255">
                                @error('cliente_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="vf-card p-3 mb-3">
                        <h2 class="h6 fw-bold mb-3">Pagamento</h2>
                        @php
                            $formasCheckout = collect($empresa->formasPagamentoLojaPublica());
                            $primeiraForma = $formasCheckout->keys()->first() ?? \App\Models\Pedido::PAGAMENTO_CARTAO_CREDITO_MAQUININHA;
                        @endphp
                        <div class="d-flex flex-column gap-2">
                            @foreach ($formasCheckout as $val => $rotulo)
                                <div class="form-check">
                                    <input class="form-check-input vf-pay-opt" type="radio" name="forma_pagamento" id="pay-{{ $val }}" value="{{ $val }}" data-pay="{{ $val }}" @checked(old('forma_pagamento', $primeiraForma) === $val)>
                                    <label class="form-check-label" for="pay-{{ $val }}">{{ $rotulo }}</label>
                                </div>
                            @endforeach
                        </div>
                        @error('forma_pagamento')<div class="text-danger small mt-2">{{ $message }}</div>@enderror

                        @if ($empresa->lojaPixConfiguradaParaCheckout())
                            <div id="vf-pay-pix-extra" class="mt-3 p-3 rounded border bg-light {{ old('forma_pagamento', $primeiraForma) === \App\Models\Pedido::PAGAMENTO_PIX ? '' : 'd-none' }}">
                                <h3 class="h6 fw-bold mb-2">Pague com PIX</h3>
                                <div class="row g-3 align-items-start">
                                    <div class="{{ $empresa->lojaPixQrCodeDataUri() ? 'col-md-7' : 'col-12' }}">
                                        @if (trim((string) $empresa->loja_pix_chave_valor) !== '')
                                            <div class="mb-3">
                                                <label class="form-label small mb-1" for="field-pix-chave">Chave PIX ({{ $empresa->lojaPixChaveRotuloTipo() }})</label>
                                                <div class="input-group input-group-sm" style="max-width: 28rem;">
                                                    <input readonly type="text" id="field-pix-chave" class="form-control font-monospace" value="{{ $empresa->loja_pix_chave_valor }}">
                                                    <button type="button" class="btn btn-outline-primary" onclick="(function(){ var t=document.getElementById('field-pix-chave'); if(!t) return; navigator.clipboard.writeText(t.value).then(function(){ alert('Chave PIX copiada.'); }).catch(function(){ t.select(); document.execCommand('copy'); }); })();">Copiar</button>
                                                </div>
                                                @if (trim((string) $empresa->loja_pix_banco) !== '')
                                                    <div class="small text-muted mt-1">Banco: {{ $empresa->loja_pix_banco }}</div>
                                                @endif
                                            </div>
                                        @endif
                                        @if (trim((string) $empresa->loja_pix_instrucoes) !== '')
                                            <div class="small mb-3" style="white-space: pre-wrap;">{{ $empresa->loja_pix_instrucoes }}</div>
                                        @endif
                                        @if (trim((string) $empresa->loja_pix_copia_cola) !== '')
                                            <label class="form-label small mb-1" for="field-pix-copia">Pix copia e cola</label>
                                            <textarea readonly class="form-control form-control-sm font-monospace" rows="4" id="field-pix-copia">{{ $empresa->loja_pix_copia_cola }}</textarea>
                                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="btn-copia-pix" onclick="(function(){ var t=document.getElementById('field-pix-copia'); if(!t) return; t.select(); navigator.clipboard.writeText(t.value).then(function(){ alert('Código PIX copiado.'); }).catch(function(){ document.execCommand('copy'); }); })();">Copiar código PIX</button>
                                        @endif
                                    </div>
                                    @if ($empresa->lojaPixQrCodeDataUri())
                                        <div class="col-md-5 text-center">
                                            <p class="small text-muted mb-2 mb-md-0">Escaneie com o app do banco ou a câmera do celular</p>
                                            <img src="{{ $empresa->lojaPixQrCodeDataUri() }}" alt="QR Code PIX" class="img-fluid border rounded bg-white p-2 mx-auto d-block" style="max-width: 220px;">
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @php
                            $dinModoOld = old('pagamento_dinheiro_modo');
                            if ($dinModoOld === null && old('pagamento_troco_para') !== null && old('pagamento_troco_para') !== '') {
                                $dinModoOld = 'com_troco';
                            }
                            $dinModoOld = $dinModoOld ?? 'exato';
                        @endphp
                        <div id="vf-pay-dinheiro-extra" class="mt-3 p-3 rounded border bg-light {{ old('forma_pagamento', $primeiraForma) === \App\Models\Pedido::PAGAMENTO_DINHEIRO ? '' : 'd-none' }}">
                            <h3 class="h6 fw-bold mb-2">Pagamento em dinheiro</h3>
                            <span class="form-label small d-block mb-2">Vai precisar de troco?</span>
                            <div class="form-check mb-2">
                                <input class="form-check-input vf-dinheiro-modo" type="radio" name="pagamento_dinheiro_modo" id="din-mod-exato" value="exato" @checked($dinModoOld === 'exato')>
                                <label class="form-check-label small" for="din-mod-exato">Não — tenho o valor exato (sem troco)</label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input vf-dinheiro-modo" type="radio" name="pagamento_dinheiro_modo" id="din-mod-troco" value="com_troco" @checked($dinModoOld === 'com_troco')>
                                <label class="form-check-label small" for="din-mod-troco">Sim — preciso de troco</label>
                            </div>
                            <div id="vf-dinheiro-valor-wrap" class="{{ $dinModoOld === 'com_troco' ? '' : 'd-none' }}">
                                <label class="form-label small mb-1" for="pagamento_troco_para">Com quanto vai pagar? <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm" style="max-width: 14rem;">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" class="form-control @error('pagamento_troco_para') is-invalid @enderror" name="pagamento_troco_para" id="pagamento_troco_para" value="{{ old('pagamento_troco_para') }}" min="0" step="0.01" placeholder="0,00" @if ($dinModoOld === 'com_troco') required @endif>
                                </div>
                                @error('pagamento_troco_para')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <p class="small text-muted mb-0 mt-2">Informe o valor da nota ou do montante (deve ser igual ou maior ao total <span id="vf-dinheiro-min-total">R$ {{ number_format($total, 2, ',', '.') }}</span>). O troco é calculado automaticamente.</p>
                            </div>
                            <p id="vf-dinheiro-ajuda-exato" class="small text-muted mb-0 mt-2 {{ $dinModoOld === 'com_troco' ? 'd-none' : '' }}">Leve dinheiro trocado para o valor exato do pedido no momento da entrega ou retirada.</p>
                        </div>
                    </div>
                    <div class="vf-card p-3">
                        <h2 class="h6 fw-bold mb-2">Observação <span class="text-muted fw-normal">(opcional, uma só)</span></h2>
                        <p class="small text-muted mb-2">Até 220 caracteres — ex.: interfone, referência, melhor horário.</p>
                        <textarea class="form-control @error('observacoes') is-invalid @enderror" name="observacoes" rows="2" placeholder="Ex.: portão azul, interfone 12…" maxlength="220">{{ old('observacoes') }}</textarea>
                        @error('observacoes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="vf-card p-3">
                        <h2 class="h6 fw-bold mb-3">Pedido</h2>
                        <ul class="list-unstyled small mb-3">
                            @foreach ($linhas as $l)
                                <li class="py-1 border-bottom">
                                    <div class="d-flex justify-content-between">
                                        <span>{{ $l['produto']->nome }} × {{ $l['quantidade'] }}</span>
                                        <span>R$ {{ number_format($l['subtotal'], 2, ',', '.') }}</span>
                                    </div>
                                    @include('partials.opcoes-pedido-item', ['opcoesLinha' => $l['opcoes'] === [] ? null : ['adicionais' => $l['opcoes']]])
                                </li>
                            @endforeach
                            <li class="d-flex justify-content-between py-1"><span>Taxa entrega</span><span id="vf-side-taxa">R$ {{ number_format($taxa, 2, ',', '.') }}</span></li>
                            <li class="small text-muted py-0" id="vf-side-taxa-rotulo">{{ $taxaRotulo }}</li>
                        </ul>
                        <div class="alert alert-warning small py-2 mb-3 {{ (($freteEntregaBloqueada ?? false) && ($tipoCheckout === \App\Models\Pedido::TIPO_ENTREGA_ENTREGA)) ? '' : 'd-none' }}" id="vf-frete-bloqueado-msg" role="alert">Este CEP está fora da área de entrega. Ajuste o CEP ou escolha retirada no balcão.</div>
                        <div class="d-flex justify-content-between fw-bold mb-3"><span>Total</span><span class="text-success" id="vf-side-total">R$ {{ number_format($total, 2, ',', '.') }}</span></div>
                        <button type="submit" class="btn btn-primary w-100" id="vf-checkout-submit" @if (($freteEntregaBloqueada ?? false) && ($tipoCheckout === \App\Models\Pedido::TIPO_ENTREGA_ENTREGA)) disabled @endif>Confirmar pedido</button>
                        <a href="{{ route('publico.carrinho', ['slug' => $slug]) }}" class="btn btn-link w-100 mt-2">Voltar ao carrinho</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
    @push('scripts')
        <script>
            (function () {
                var din = '{{ \App\Models\Pedido::PAGAMENTO_DINHEIRO }}';
                var pix = '{{ \App\Models\Pedido::PAGAMENTO_PIX }}';
                var boxDin = document.getElementById('vf-pay-dinheiro-extra');
                var boxPix = document.getElementById('vf-pay-pix-extra');
                document.querySelectorAll('.vf-pay-opt').forEach(function (r) {
                    r.addEventListener('change', function () {
                        if (boxDin) {
                            if (this.value === din) boxDin.classList.remove('d-none');
                            else boxDin.classList.add('d-none');
                        }
                        if (boxPix) {
                            if (this.value === pix) boxPix.classList.remove('d-none');
                            else boxPix.classList.add('d-none');
                        }
                    });
                });

                function syncDinheiroModo() {
                    var wrapVal = document.getElementById('vf-dinheiro-valor-wrap');
                    var ajudaExato = document.getElementById('vf-dinheiro-ajuda-exato');
                    var inp = document.getElementById('pagamento_troco_para');
                    var troco = document.getElementById('din-mod-troco');
                    if (!wrapVal || !inp) return;
                    var precisa = troco && troco.checked;
                    wrapVal.classList.toggle('d-none', !precisa);
                    inp.required = !!precisa;
                    if (!precisa) {
                        inp.value = '';
                        inp.classList.remove('is-invalid');
                    }
                    if (ajudaExato) ajudaExato.classList.toggle('d-none', !!precisa);
                }
                document.querySelectorAll('.vf-dinheiro-modo').forEach(function (r) {
                    r.addEventListener('change', syncDinheiroModo);
                });
                syncDinheiroModo();

                var entrega = '{{ \App\Models\Pedido::TIPO_ENTREGA_ENTREGA }}';
                var sub = {{ number_format($subtotal, 2, '.', '') }};
                var taxaEnt = {{ number_format($taxaSeEntrega, 2, '.', '') }};
                var rotuloEnt = @json($rotuloSeEntrega);
                var entregaBloq = {{ ($freteEntregaBloqueadaSeEntrega ?? false) ? 'true' : 'false' }};
                var rotuloBal = 'Retirada no balcão';
                var freteUrl = @json(route('publico.frete.resumo', ['slug' => $slug]));
                var osrmCheckout = {{ ($checkoutOsrm ?? false) ? 'true' : 'false' }};
                var calcEntregaUrl = @json($calcularEntregaApiUrl ?? '');
                var slugLoja = @json($slug);
                var taxaBasePadrao = {{ number_format($empresa->lojaTaxaEntregaPadraoEfetiva(), 2, '.', '') }};
                var fmt = function (n) {
                    return n.toFixed(2).replace('.', ',');
                };
                var boxEnt = document.getElementById('vf-checkout-entrega-fields');
                var cepEl = document.getElementById('cep_entrega');
                var endEl = document.getElementById('endereco');
                var elTaxa = document.getElementById('vf-side-taxa');
                var elRotulo = document.getElementById('vf-side-taxa-rotulo');
                var elTotal = document.getElementById('vf-side-total');
                var elBloqMsg = document.getElementById('vf-frete-bloqueado-msg');
                var btnSubmit = document.getElementById('vf-checkout-submit');
                var elDinMinTot = document.getElementById('vf-dinheiro-min-total');
                var elOsrmMeta = document.getElementById('vf-osrm-frete-meta');
                var csrf = document.querySelector('meta[name="csrf-token"]');
                var csrfToken = csrf ? csrf.getAttribute('content') : '';
                var debounceTimer = null;
                function gv(id) {
                    var e = document.getElementById(id);
                    return e ? (e.value || '').trim() : '';
                }
                function syncResumo(isEnt) {
                    var bloq = !!(isEnt && entregaBloq);
                    var taxa = isEnt ? taxaEnt : 0;
                    var tot = Math.round((sub + taxa) * 100) / 100;
                    if (bloq) {
                        taxa = 0;
                        tot = Math.round(sub * 100) / 100;
                    }
                    if (elTaxa) elTaxa.textContent = 'R$ ' + fmt(taxa);
                    if (elRotulo) elRotulo.textContent = isEnt ? rotuloEnt : rotuloBal;
                    if (elTotal) elTotal.textContent = 'R$ ' + fmt(tot);
                    if (elDinMinTot) elDinMinTot.textContent = 'R$ ' + fmt(tot);
                    if (elBloqMsg) elBloqMsg.classList.toggle('d-none', !bloq);
                    if (btnSubmit) btnSubmit.disabled = bloq;
                }
                function pedirFreteAtualizado() {
                    if (!cepEl || !csrfToken) return;
                    var r = document.querySelector('.vf-tipo-entrega:checked');
                    if (!r || r.value !== entrega) return;
                    if (elTaxa) elTaxa.textContent = '…';
                    if (osrmCheckout && calcEntregaUrl) {
                        var cepDig = (cepEl.value || '').replace(/\D+/g, '');
                        if (cepDig.length !== 8) {
                            syncResumo(true);
                            return;
                        }
                        fetch(calcEntregaUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                slug: slugLoja,
                                cep: cepEl.value,
                                rua: endEl ? endEl.value : '',
                                numero: gv('entrega_numero'),
                                bairro: gv('entrega_bairro'),
                                cidade: gv('entrega_cidade'),
                                estado: gv('entrega_estado'),
                                subtotal_pedido: sub
                            })
                        }).then(function (res) { return res.json(); }).then(function (data) {
                            if (elOsrmMeta) {
                                elOsrmMeta.classList.add('d-none');
                                elOsrmMeta.textContent = '';
                            }
                            if (!data) {
                                syncResumo(true);
                                return;
                            }
                            if (!data.success) {
                                rotuloEnt = data.message || 'Não foi possível calcular o frete. Confira o endereço.';
                                taxaEnt = taxaBasePadrao;
                                entregaBloq = false;
                                syncResumo(true);
                                return;
                            }
                            taxaEnt = parseFloat(data.taxa_entrega);
                            if (isNaN(taxaEnt)) taxaEnt = 0;
                            entregaBloq = !!data.entrega_bloqueada;
                            rotuloEnt = data.endereco_formatado
                                ? ('Aprox. ' + (data.distancia_km != null ? Number(data.distancia_km).toFixed(1).replace('.', ',') + ' km' : '') +
                                    (data.tempo_minutos != null ? ', ~' + data.tempo_minutos + ' min — ' : ' — ') +
                                    data.endereco_formatado.substring(0, 120))
                                : ('Rota ~' + (data.distancia_km != null ? Number(data.distancia_km).toFixed(1).replace('.', ',') + ' km' : ''));
                            if (elOsrmMeta && data.distancia_km != null) {
                                elOsrmMeta.textContent = 'Distância pela rota ~' + Number(data.distancia_km).toFixed(1).replace('.', ',') +
                                    ' km · tempo estimado ~' + (data.tempo_minutos != null ? data.tempo_minutos : '—') + ' min';
                                elOsrmMeta.classList.remove('d-none');
                            }
                            syncResumo(true);
                        }).catch(function () {
                            syncResumo(true);
                        });
                        return;
                    }
                    if (!freteUrl) return;
                    fetch(freteUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ cep: cepEl.value, subtotal: sub })
                    }).then(function (res) { return res.json(); }).then(function (data) {
                        if (!data || !data.ok || data.incomplete) {
                            syncResumo(true);
                            return;
                        }
                        taxaEnt = parseFloat(data.taxa);
                        if (isNaN(taxaEnt)) taxaEnt = 0;
                        rotuloEnt = data.rotulo || '';
                        entregaBloq = !!data.entrega_bloqueada;
                        syncResumo(true);
                    }).catch(function () {
                        syncResumo(true);
                    });
                }
                function agendarFrete() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(pedirFreteAtualizado, 450);
                }
                function syncEntregaFields() {
                    var r = document.querySelector('.vf-tipo-entrega:checked');
                    var isEnt = r && r.value === entrega;
                    if (boxEnt) {
                        boxEnt.classList.toggle('d-none', !isEnt);
                    }
                    if (cepEl) {
                        cepEl.required = !!isEnt;
                    }
                    if (endEl) {
                        endEl.required = !!isEnt;
                    }
                    syncResumo(!!isEnt);
                    if (isEnt) agendarFrete();
                }
                document.querySelectorAll('.vf-tipo-entrega').forEach(function (r) {
                    r.addEventListener('change', syncEntregaFields);
                });
                if (cepEl) {
                    cepEl.addEventListener('input', function () {
                        var r = document.querySelector('.vf-tipo-entrega:checked');
                        if (r && r.value === entrega) agendarFrete();
                    });
                    cepEl.addEventListener('change', function () {
                        var r = document.querySelector('.vf-tipo-entrega:checked');
                        if (r && r.value === entrega) pedirFreteAtualizado();
                    });
                }
                ['endereco', 'entrega_numero', 'entrega_bairro', 'entrega_cidade', 'entrega_estado'].forEach(function (id) {
                    var el = document.getElementById(id);
                    if (!el) return;
                    el.addEventListener('input', function () {
                        var r = document.querySelector('.vf-tipo-entrega:checked');
                        if (r && r.value === entrega && osrmCheckout) agendarFrete();
                    });
                    el.addEventListener('change', function () {
                        var r = document.querySelector('.vf-tipo-entrega:checked');
                        if (r && r.value === entrega && osrmCheckout) pedirFreteAtualizado();
                    });
                });
                syncEntregaFields();
            })();
        </script>
    @endpush
@endsection
