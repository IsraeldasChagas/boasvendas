@extends('layouts.publico')

@section('title', 'Checkout — '.$empresa->nome)

@section('content')
    <div class="container">
        <h1 class="h4 fw-bold mb-3">Finalizar pedido</h1>
        <form action="{{ route('publico.checkout.finalizar', ['slug' => $slug]) }}" method="post">
            @csrf
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="vf-card p-3 mb-3">
                        <h2 class="h6 fw-bold mb-3">Seus dados e entrega</h2>
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
                            <div class="col-md-8">
                                <label class="form-label small" for="endereco">Endereço de entrega</label>
                                <input type="text" class="form-control @error('endereco') is-invalid @enderror" id="endereco" name="endereco" value="{{ old('endereco') }}" required maxlength="255">
                                @error('endereco')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small" for="complemento">Complemento</label>
                                <input type="text" class="form-control @error('complemento') is-invalid @enderror" id="complemento" name="complemento" value="{{ old('complemento') }}" maxlength="120">
                                @error('complemento')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                            <li class="d-flex justify-content-between py-1"><span>Entrega</span><span>R$ {{ number_format($taxa, 2, ',', '.') }}</span></li>
                        </ul>
                        <div class="d-flex justify-content-between fw-bold mb-3"><span>Total</span><span class="text-success">R$ {{ number_format($total, 2, ',', '.') }}</span></div>
                        <button type="submit" class="btn btn-primary w-100">Confirmar pedido</button>
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
            })();
        </script>
    @endpush
@endsection
