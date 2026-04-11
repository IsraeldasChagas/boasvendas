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
                                @if ($modoFreteLoja === \App\Models\Empresa::LOJA_FRETE_GOOGLE_DISTANCIA)
                                    <p class="small text-muted mb-0">O frete usa a <strong>rota de carro</strong> (Google) entre a loja e o endereço informado. No carrinho a simulação usa só o CEP; no pedido vale o endereço completo.</p>
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

                        <div id="vf-pay-dinheiro-extra" class="mt-3 p-3 rounded border bg-light {{ old('forma_pagamento', $primeiraForma) === \App\Models\Pedido::PAGAMENTO_DINHEIRO ? '' : 'd-none' }}">
                            <label class="form-label small mb-1" for="pagamento_troco_para">Vai pagar com quanto em dinheiro? <span class="text-muted">(opcional)</span></label>
                            <div class="input-group input-group-sm" style="max-width: 14rem;">
                                <span class="input-group-text">R$</span>
                                <input type="number" class="form-control @error('pagamento_troco_para') is-invalid @enderror" name="pagamento_troco_para" id="pagamento_troco_para" value="{{ old('pagamento_troco_para') }}" min="0" step="0.01" placeholder="Ex.: 100,00">
                            </div>
                            @error('pagamento_troco_para')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <p class="small text-muted mb-0 mt-2">Informe o valor da cédula ou do montante (deve ser ≥ total R$ {{ number_format($total, 2, ',', '.') }}) para o entregador levar o troco. Deixe em branco se for pagar o valor exato.</p>
                        </div>
                    </div>
                    <div class="vf-card p-3">
                        <h2 class="h6 fw-bold mb-2">Observações</h2>
                        <textarea class="form-control @error('observacoes') is-invalid @enderror" name="observacoes" rows="2" placeholder="Ex.: portão ou interfone, ponto de referência, melhor horário para entrega…" maxlength="1000">{{ old('observacoes') }}</textarea>
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

                var entrega = '{{ \App\Models\Pedido::TIPO_ENTREGA_ENTREGA }}';
                var sub = {{ number_format($subtotal, 2, '.', '') }};
                var taxaEnt = {{ number_format($taxaSeEntrega, 2, '.', '') }};
                var rotuloEnt = @json($rotuloSeEntrega);
                var entregaBloq = {{ ($freteEntregaBloqueadaSeEntrega ?? false) ? 'true' : 'false' }};
                var rotuloBal = 'Retirada no balcão';
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
                    if (elBloqMsg) elBloqMsg.classList.toggle('d-none', !bloq);
                    if (btnSubmit) btnSubmit.disabled = bloq;
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
                }
                document.querySelectorAll('.vf-tipo-entrega').forEach(function (r) {
                    r.addEventListener('change', syncEntregaFields);
                });
                syncEntregaFields();
            })();
        </script>
    @endpush
@endsection
