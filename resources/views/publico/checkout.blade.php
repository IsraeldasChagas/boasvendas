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
                        <div class="d-flex flex-wrap gap-2">
                            @foreach (\App\Models\Pedido::formasPagamentoRotulos() as $val => $rotulo)
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="forma_pagamento" id="pay-{{ $val }}" value="{{ $val }}" @checked(old('forma_pagamento', \App\Models\Pedido::PAGAMENTO_PIX) === $val)>
                                    <label class="form-check-label" for="pay-{{ $val }}">{{ $rotulo }}</label>
                                </div>
                            @endforeach
                        </div>
                        @error('forma_pagamento')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                    </div>
                    <div class="vf-card p-3">
                        <h2 class="h6 fw-bold mb-2">Observações</h2>
                        <textarea class="form-control @error('observacoes') is-invalid @enderror" name="observacoes" rows="2" placeholder="Ex.: sem cebola" maxlength="1000">{{ old('observacoes') }}</textarea>
                        @error('observacoes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="vf-card p-3">
                        <h2 class="h6 fw-bold mb-3">Pedido</h2>
                        <ul class="list-unstyled small mb-3">
                            @foreach ($linhas as $l)
                                <li class="d-flex justify-content-between py-1 border-bottom">
                                    <span>{{ $l['produto']->nome }} × {{ $l['quantidade'] }}</span>
                                    <span>R$ {{ number_format($l['subtotal'], 2, ',', '.') }}</span>
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
@endsection
