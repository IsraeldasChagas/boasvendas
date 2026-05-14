@extends('layouts.empresa')

@section('title')
Fechamento — Mesa #{{ $comanda->mesa?->numero }}
@endsection

@section('content')
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Resumo da comanda</div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    @foreach ($comanda->itens as $it)
                        @continue($it->status === \App\Enums\Mesas\ComandaItemStatus::Cancelado)
                        <li class="d-flex justify-content-between border-bottom py-2">
                            <span>{{ $it->nome_produto }} × {{ $it->quantidade }}</span>
                            <span>R$ {{ number_format((float) $it->valor_total, 2, ',', '.') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Pagamento e totais</div>
            <div class="card-body">
                <form method="post" action="{{ route('empresa.mesas.fechamento.finalizar', $comanda) }}" id="formFechamento">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label">Taxa serviço (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="taxa_servico_percentual" id="inpTaxa" value="{{ old('taxa_servico_percentual', (float) $config->taxa_servico_padrao_percent) }}" class="form-control form-control-lg">
                        <div class="form-text">Use 0 para não cobrar taxa nesta conta.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Desconto (R$)</label>
                        <input type="number" step="0.01" min="0" name="desconto" id="inpDesconto" value="{{ old('desconto', 0) }}" class="form-control form-control-lg">
                    </div>
                    <hr>
                    <p class="mb-1"><strong>Subtotal itens:</strong> R$ <span id="lblSub">{{ number_format($subtotalItens, 2, ',', '.') }}</span></p>
                    <p class="mb-1"><strong>Total a pagar:</strong> R$ <span id="lblTotal" class="fs-4 text-success">{{ number_format($subtotalItens, 2, ',', '.') }}</span></p>

                    <div id="pagamentosLinhas" class="mt-3"></div>
                    <button type="button" class="btn btn-outline-primary w-100 my-2" id="btnAddPag">+ Dividir / adicionar pagamento</button>

                    <div class="d-grid gap-2 mt-3">
                        <button type="submit" class="btn btn-success btn-lg">Finalizar pagamento</button>
                        <a href="{{ route('empresa.comandas.pre-conta', $comanda) }}" target="_blank" class="btn btn-outline-secondary btn-lg">Imprimir pré-conta</a>
                        <a href="{{ route('empresa.comandas.fiscal-futuro', $comanda) }}" class="btn btn-outline-dark btn-lg">Emitir NFC-e (futuro)</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const subItens = {{ number_format($subtotalItens, 2, '.', '') }};
    const inpTaxa = document.getElementById('inpTaxa');
    const inpDesconto = document.getElementById('inpDesconto');
    const lblTotal = document.getElementById('lblTotal');
    const box = document.getElementById('pagamentosLinhas');
    const btnAdd = document.getElementById('btnAddPag');
    const formas = @json($formasPagamento);
    let idx = 0;

    function brl(n) {
        return n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function totalEsperado() {
        const tx = parseFloat(inpTaxa.value.replace(',', '.')) || 0;
        const desc = parseFloat(inpDesconto.value.replace(',', '.')) || 0;
        const taxaValor = Math.round(subItens * (tx / 100) * 100) / 100;
        return Math.max(0, Math.round((subItens + taxaValor - desc) * 100) / 100);
    }

    function refreshTotal() {
        lblTotal.textContent = brl(totalEsperado());
        distribuirPagamentoUnico();
    }

    function addLinha(forma, valor) {
        const i = idx++;
        const sel = Object.keys(formas).map(k =>
            `<option value="${k}" ${k === forma ? 'selected' : ''}>${formas[k]}</option>`
        ).join('');
        box.insertAdjacentHTML('beforeend', `
            <div class="border rounded p-2 mb-2 vf-linha-pag" data-idx="${i}">
                <label class="form-label small mb-1">Forma ${i + 1}</label>
                <select name="pagamentos[${i}][forma_pagamento]" class="form-select form-select-lg mb-2">${sel}</select>
                <label class="form-label small mb-1">Valor pago</label>
                <input type="number" step="0.01" min="0" name="pagamentos[${i}][valor_pago]" class="form-control form-control-lg vf-valor-pag" value="${valor.toFixed(2)}">
                <label class="form-label small mb-1 mt-2">Troco (se dinheiro)</label>
                <input type="number" step="0.01" min="0" name="pagamentos[${i}][troco]" class="form-control" value="0">
            </div>
        `);
    }

    function distribuirPagamentoUnico() {
        const linhas = box.querySelectorAll('.vf-linha-pag');
        if (linhas.length === 1) {
            const inp = linhas[0].querySelector('.vf-valor-pag');
            if (inp) inp.value = totalEsperado().toFixed(2);
        }
    }

    btnAdd.addEventListener('click', function () {
        addLinha(Object.keys(formas)[0] || 'dinheiro', 0);
    });

    inpTaxa.addEventListener('input', refreshTotal);
    inpDesconto.addEventListener('input', refreshTotal);

    const primeiraForma = Object.keys(formas)[0] || 'dinheiro';
    addLinha(primeiraForma, totalEsperado());
    refreshTotal();
})();
</script>
@endpush
@endsection
