@extends('layouts.publico')

@section('title', 'Carrinho — '.$empresa->nome)

@section('content')
    <div class="container">
        <h1 class="h4 fw-bold mb-3">Carrinho</h1>
        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show small" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        @endif
        @if (session('warning'))
            <div class="alert alert-warning alert-dismissible fade show small" role="alert">
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        @endif
        @if ($linhas === [])
            <div class="vf-card p-4 text-center text-muted">
                <p class="mb-3">Seu carrinho está vazio.</p>
                <a href="{{ route('publico.loja', ['slug' => $slug]) }}" class="btn btn-primary">Ver cardápio</a>
            </div>
        @else
            <form action="{{ route('publico.carrinho.atualizar', ['slug' => $slug]) }}" method="post">
                @csrf
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="vf-card table-responsive">
                            <table class="table table-hover align-middle mb-0 vf-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-center" style="width: 7rem;">Qtd</th>
                                        <th class="text-end">Unit.</th>
                                        <th class="text-end">Total</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($linhas as $l)
                                        @php $p = $l['produto']; @endphp
                                        <tr>
                                            <td class="fw-medium">
                                                <div class="d-flex align-items-start gap-2">
                                                    @if ($p->urlFoto())
                                                        <img src="{{ $p->urlFoto() }}" alt="" width="48" height="48" class="rounded border flex-shrink-0 object-fit-cover" style="width:48px;height:48px;">
                                                    @endif
                                                    <div>
                                                        <a href="{{ route('publico.produto', ['slug' => $slug, 'produto_id' => $p->id]) }}" class="text-decoration-none text-dark">{{ $p->nome }}</a>
                                                        @include('partials.opcoes-pedido-item', ['opcoesLinha' => $l['opcoes'] === [] ? null : ['adicionais' => $l['opcoes']]])
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <input type="number" class="form-control form-control-sm text-center" name="quantidade[{{ $l['line_index'] }}]" value="{{ $l['quantidade'] }}" min="0" max="99">
                                            </td>
                                            <td class="text-end">R$ {{ number_format((float) $l['preco_unitario'], 2, ',', '.') }}</td>
                                            <td class="text-end fw-semibold">R$ {{ number_format($l['subtotal'], 2, ',', '.') }}</td>
                                            <td class="text-end">
                                                <button type="submit"
                                                    formaction="{{ route('publico.carrinho.remover', ['slug' => $slug]) }}"
                                                    name="line_index"
                                                    value="{{ $l['line_index'] }}"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Remover"
                                                    onclick="return confirm('Remover este item?');">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn btn-outline-secondary btn-sm mt-2">Atualizar quantidades</button>
                        <a href="{{ route('publico.loja', ['slug' => $slug]) }}" class="btn btn-link ps-0 mt-2 d-block"><i class="bi bi-arrow-left me-1"></i>Continuar comprando</a>
                    </div>
                    <div class="col-lg-4">
                        <div class="vf-card p-3 mb-3">
                            <h2 class="h6 fw-bold">Como receber</h2>
                            <form action="{{ route('publico.carrinho.entrega-prefs', ['slug' => $slug]) }}" method="post" class="mb-3">
                                @csrf
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="modo" id="car-mod-entrega" value="{{ \App\Models\Pedido::TIPO_ENTREGA_ENTREGA }}" @checked($prefs['modo'] === \App\Models\Pedido::TIPO_ENTREGA_ENTREGA)>
                                    <label class="form-check-label small" for="car-mod-entrega">Entrega</label>
                                </div>
                                @if ($permiteBalcao)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="modo" id="car-mod-balcao" value="{{ \App\Models\Pedido::TIPO_ENTREGA_BALCAO }}" @checked($prefs['modo'] === \App\Models\Pedido::TIPO_ENTREGA_BALCAO)>
                                        <label class="form-check-label small" for="car-mod-balcao">Retirada no balcão <span class="text-success">(sem taxa)</span></label>
                                    </div>
                                @endif
                                <label class="form-label small text-muted mb-0" for="car-cep">CEP <span class="text-muted">(para simular frete)</span></label>
                                <div class="input-group input-group-sm mb-2">
                                    <input type="text" class="form-control" id="car-cep" name="cep" value="{{ $prefs['cep'] !== '' ? substr($prefs['cep'], 0, 5).'-'.substr($prefs['cep'], 5) : '' }}" placeholder="00000-000" maxlength="9" autocomplete="postal-code">
                                    <button type="submit" class="btn btn-outline-primary">Atualizar</button>
                                </div>
                                <p class="small text-muted mb-0">No checkout o CEP é obrigatório para entrega.</p>
                            </form>
                        </div>
                        <div class="vf-card p-3">
                            <h2 class="h6 fw-bold">Resumo</h2>
                            <div class="d-flex justify-content-between small mb-2"><span>Subtotal</span><span>R$ {{ number_format($subtotal, 2, ',', '.') }}</span></div>
                            <div class="d-flex justify-content-between small mb-1"><span>Taxa entrega</span><span>R$ {{ number_format($taxa, 2, ',', '.') }}</span></div>
                            <p class="small text-muted mb-2">{{ $taxaRotulo }}</p>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold mb-3"><span>Total</span><span class="text-success">R$ {{ number_format($total, 2, ',', '.') }}</span></div>
                            <a href="{{ route('publico.checkout', ['slug' => $slug]) }}" class="btn btn-success w-100">Ir para checkout</a>
                        </div>
                    </div>
                </div>
            </form>
        @endif
    </div>
@endsection
