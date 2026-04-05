@extends('layouts.publico')

@section('title', 'Carrinho — '.$empresa->nome)

@section('content')
    <div class="container">
        <h1 class="h4 fw-bold mb-3">Carrinho</h1>
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
                                                <a href="{{ route('publico.produto', ['slug' => $slug, 'produto' => $p->id]) }}" class="text-decoration-none text-dark">{{ $p->nome }}</a>
                                            </td>
                                            <td class="text-center">
                                                <input type="number" class="form-control form-control-sm text-center" name="quantidade[{{ $p->id }}]" value="{{ $l['quantidade'] }}" min="0" max="99">
                                            </td>
                                            <td class="text-end">R$ {{ number_format((float) $p->preco, 2, ',', '.') }}</td>
                                            <td class="text-end fw-semibold">R$ {{ number_format($l['subtotal'], 2, ',', '.') }}</td>
                                            <td class="text-end">
                                                <button type="submit"
                                                    formaction="{{ route('publico.carrinho.remover', ['slug' => $slug]) }}"
                                                    name="produto_id"
                                                    value="{{ $p->id }}"
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
                        <div class="vf-card p-3">
                            <h2 class="h6 fw-bold">Resumo</h2>
                            <div class="d-flex justify-content-between small mb-2"><span>Subtotal</span><span>R$ {{ number_format($subtotal, 2, ',', '.') }}</span></div>
                            <div class="d-flex justify-content-between small mb-2"><span>Taxa entrega</span><span>R$ {{ number_format($taxa, 2, ',', '.') }}</span></div>
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
