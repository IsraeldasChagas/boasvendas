@extends('layouts.empresa')

@section('title', 'PDV — Novo pedido')

@push('styles')
<style>
    .vf-pdv-carrinho { max-height: 50vh; overflow-y: auto; }
    .vf-pdv-tab.active { background-color: #0d6efd; color: white; }
    .vf-pdv-resumo { position: sticky; bottom: 0; }
</style>
@endpush

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h5 fw-bold mb-1"><i class="bi bi-cash-coin text-primary me-1"></i>PDV — Novo pedido</h1>
            <p class="text-muted small mb-0">Lance pedidos do balcão ou recebidos por WhatsApp/telefone. Frete calculado pelas mesmas regras da vitrine.</p>
        </div>
        <a href="{{ route('empresa.pedidos.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-list-ul me-1"></i> Ver todos os pedidos
        </a>
    </div>

    @if ($produtos->isEmpty())
        <div class="alert alert-warning">
            <strong>Sem produtos ativos cadastrados.</strong>
            Vá em <a href="{{ route('empresa.produtos.index') }}">Produtos</a> e cadastre ao menos um produto para usar o PDV.
        </div>
    @endif

    <form id="vf-pdv-form" method="post" action="{{ route('empresa.pdv.store') }}">
        @csrf

        <input type="hidden" name="canal" id="vf-pdv-canal" value="{{ \App\Models\Pedido::CANAL_BALCAO }}">
        <input type="hidden" name="acao" id="vf-pdv-acao" value="finalizar">

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="vf-card border border-primary border-2 shadow-sm overflow-hidden rounded-2 mb-3">
                    <div class="p-3 bg-primary-subtle bg-opacity-25 border-bottom border-primary border-opacity-25">
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-outline-primary active vf-pdv-tab" data-canal="{{ \App\Models\Pedido::CANAL_BALCAO }}" data-tipo="{{ \App\Models\Pedido::TIPO_ENTREGA_BALCAO }}">
                                <i class="bi bi-shop me-1"></i> Balcão
                            </button>
                            <button type="button" class="btn btn-outline-primary vf-pdv-tab" data-canal="{{ \App\Models\Pedido::CANAL_WHATSAPP }}" data-tipo="{{ \App\Models\Pedido::TIPO_ENTREGA_ENTREGA }}">
                                <i class="bi bi-whatsapp me-1"></i> WhatsApp / Telefone
                            </button>
                        </div>
                    </div>
                    <div class="p-3">
                        <input type="hidden" name="tipo_entrega" id="vf-pdv-tipo-entrega" value="{{ $temBalcao ? \App\Models\Pedido::TIPO_ENTREGA_BALCAO : \App\Models\Pedido::TIPO_ENTREGA_ENTREGA }}">

                        <label class="form-label small fw-semibold mb-2" for="vf-pdv-busca">Adicionar produtos</label>
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="vf-pdv-busca" placeholder="Filtrar produtos por nome...">
                        </div>

                        <div class="input-group input-group-sm">
                            <select class="form-select" id="vf-pdv-select-produto" aria-label="Selecione um produto">
                                <option value="">— Selecione um produto —</option>
                                @foreach ($produtos as $p)
                                    <option value="{{ $p->id }}"
                                            data-nome="{{ $p->nome }}"
                                            data-preco="{{ $p->preco }}"
                                            data-estoque="{{ $p->estoque ?? '' }}">{{ $p->nome }} — R$ {{ number_format((float) $p->preco, 2, ',', '.') }}@if ($p->estoque !== null) · estoque {{ $p->estoque }}@endif</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-primary" id="vf-pdv-btn-adicionar" title="Adicionar produto ao pedido">
                                <i class="bi bi-plus-circle me-1"></i>Adicionar
                            </button>
                        </div>
                        <p class="small text-muted mb-0 mt-2" id="vf-pdv-busca-info">
                            <span id="vf-pdv-busca-total">{{ $produtos->count() }}</span> produto(s) disponíveis.
                        </p>
                    </div>
                </div>

                <div class="vf-card border border-secondary border-2 shadow-sm overflow-hidden rounded-2 mb-3">
                    <div class="p-3 bg-secondary-subtle bg-opacity-25 border-bottom border-secondary border-opacity-25">
                        <h2 class="h6 fw-bold mb-0"><i class="bi bi-person text-secondary me-1"></i>Cliente</h2>
                    </div>
                    <div class="p-3">
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label small" for="vf-pdv-cliente-nome">Nome <span class="text-muted small" id="vf-pdv-nome-req"></span></label>
                                <input type="text" class="form-control form-control-sm" id="vf-pdv-cliente-nome" name="cliente_nome" maxlength="120">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small" for="vf-pdv-cliente-tel">Telefone <span class="text-muted small" id="vf-pdv-tel-req"></span></label>
                                <input type="text" inputmode="tel" class="form-control form-control-sm" id="vf-pdv-cliente-tel" name="cliente_telefone" maxlength="32" placeholder="(00) 00000-0000">
                            </div>
                        </div>

                        <div id="vf-pdv-bloco-entrega" class="d-none">
                            <hr class="my-3">
                            <h6 class="small fw-semibold mb-2"><i class="bi bi-geo-alt me-1"></i>Endereço de entrega</h6>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label small" for="vf-pdv-cep">CEP <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" inputmode="numeric" maxlength="9" class="form-control" id="vf-pdv-cep" name="cep_entrega" placeholder="00000-000">
                                        <button type="button" class="btn btn-outline-primary" id="vf-pdv-calc-frete" title="Calcular frete">
                                            <i class="bi bi-calculator"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small" for="vf-pdv-rua">Rua <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" id="vf-pdv-rua" name="endereco" maxlength="255">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small" for="vf-pdv-numero">Nº</label>
                                    <input type="text" class="form-control form-control-sm" id="vf-pdv-numero" name="entrega_numero" maxlength="32">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small" for="vf-pdv-bairro">Bairro</label>
                                    <input type="text" class="form-control form-control-sm" id="vf-pdv-bairro" name="entrega_bairro" maxlength="120">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small" for="vf-pdv-cidade">Cidade</label>
                                    <input type="text" class="form-control form-control-sm" id="vf-pdv-cidade" name="entrega_cidade" maxlength="120">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small" for="vf-pdv-uf">UF</label>
                                    <input type="text" maxlength="2" class="form-control form-control-sm text-uppercase" id="vf-pdv-uf" name="entrega_estado">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small" for="vf-pdv-complemento">Complemento</label>
                                    <input type="text" class="form-control form-control-sm" id="vf-pdv-complemento" name="complemento" maxlength="120" placeholder="Apto, ponto de referência, etc.">
                                </div>
                            </div>
                            <p class="small text-muted mb-0 mt-2" id="vf-pdv-frete-info">Digite o CEP e clique no botão da calculadora para atualizar o frete.</p>
                        </div>
                    </div>
                </div>

                <div class="vf-card border border-info border-2 shadow-sm overflow-hidden rounded-2 mb-3">
                    <div class="p-3 bg-info-subtle bg-opacity-25 border-bottom border-info border-opacity-25">
                        <h2 class="h6 fw-bold mb-0"><i class="bi bi-credit-card text-info me-1"></i>Pagamento</h2>
                    </div>
                    <div class="p-3">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small" for="vf-pdv-forma">Forma <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="vf-pdv-forma" name="forma_pagamento" required>
                                    @foreach ($formasPagamento as $val => $rotulo)
                                        <option value="{{ $val }}">{{ $rotulo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 d-none" id="vf-pdv-bloco-troco">
                                <label class="form-label small" for="vf-pdv-troco">Troco para (R$)</label>
                                <input type="text" inputmode="decimal" class="form-control form-control-sm" id="vf-pdv-troco" name="pagamento_troco_para" placeholder="Ex.: 100,00">
                            </div>
                            <div class="col-12">
                                <label class="form-label small" for="vf-pdv-obs">Observações do pedido</label>
                                <textarea class="form-control form-control-sm" id="vf-pdv-obs" name="observacoes" rows="2" maxlength="500" placeholder="Sem cebola, embalar separado, etc."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="vf-card border border-success border-2 shadow-sm overflow-hidden rounded-2 vf-pdv-resumo">
                    <div class="p-3 bg-success-subtle bg-opacity-25 border-bottom border-success border-opacity-25 d-flex align-items-center justify-content-between">
                        <h2 class="h6 fw-bold mb-0"><i class="bi bi-bag-check text-success me-1"></i>Resumo do pedido</h2>
                        <span class="badge bg-success" id="vf-pdv-qtd-itens">0 itens</span>
                    </div>
                    <div class="p-3">
                        <div class="vf-pdv-carrinho mb-3" id="vf-pdv-carrinho">
                            <p class="text-muted small text-center py-4 mb-0" id="vf-pdv-carrinho-vazio">
                                <i class="bi bi-basket3 fs-3 d-block mb-2"></i>
                                Clique nos produtos ao lado para adicionar.
                            </p>
                        </div>

                        <div class="border-top pt-3">
                            <div class="d-flex justify-content-between mb-1 small">
                                <span>Subtotal</span>
                                <span id="vf-pdv-subtotal">R$ 0,00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1 small d-none" id="vf-pdv-linha-frete">
                                <span>Taxa de entrega</span>
                                <span id="vf-pdv-frete">R$ 0,00</span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold fs-5 border-top pt-2 mt-2">
                                <span>Total</span>
                                <span class="text-success" id="vf-pdv-total">R$ 0,00</span>
                            </div>
                        </div>

                        <div class="alert alert-warning small py-2 mb-3 mt-3 d-none" id="vf-pdv-alerta-frete">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            <span id="vf-pdv-alerta-frete-msg"></span>
                        </div>

                        <div class="d-grid gap-2 mt-3">
                            <button type="button" class="btn btn-success btn-lg" id="vf-pdv-btn-finalizar">
                                <i class="bi bi-check2-circle me-1"></i> Finalizar pedido
                            </button>
                            <button type="button" class="btn btn-outline-primary d-none" id="vf-pdv-btn-whatsapp">
                                <i class="bi bi-whatsapp me-1"></i> Salvar e enviar resumo pelo WhatsApp
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="vf-pdv-itens-hidden"></div>
    </form>

    @if ($errors->any())
        <div class="alert alert-danger mt-3">
            <strong>Não foi possível salvar o pedido:</strong>
            <ul class="mb-0 mt-1 small">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection

@push('scripts')
<script>
(function () {
    const itens = [];
    let frete = { taxa: 0, rotulo: '', bloqueada: false, calculado: false };

    const elTabs = document.querySelectorAll('.vf-pdv-tab');
    const elCanal = document.getElementById('vf-pdv-canal');
    const elTipoEntrega = document.getElementById('vf-pdv-tipo-entrega');
    const elAcao = document.getElementById('vf-pdv-acao');
    const elBlocoEntrega = document.getElementById('vf-pdv-bloco-entrega');
    const elNomeReq = document.getElementById('vf-pdv-nome-req');
    const elTelReq = document.getElementById('vf-pdv-tel-req');
    const elBusca = document.getElementById('vf-pdv-busca');
    const elSelectProduto = document.getElementById('vf-pdv-select-produto');
    const elBtnAdicionar = document.getElementById('vf-pdv-btn-adicionar');
    const elBuscaTotal = document.getElementById('vf-pdv-busca-total');
    const elBuscaInfo = document.getElementById('vf-pdv-busca-info');
    const elCarrinho = document.getElementById('vf-pdv-carrinho');
    const elVazio = document.getElementById('vf-pdv-carrinho-vazio');
    const elQtd = document.getElementById('vf-pdv-qtd-itens');
    const elSub = document.getElementById('vf-pdv-subtotal');
    const elFrete = document.getElementById('vf-pdv-frete');
    const elLinhaFrete = document.getElementById('vf-pdv-linha-frete');
    const elTotal = document.getElementById('vf-pdv-total');
    const elFreteInfo = document.getElementById('vf-pdv-frete-info');
    const elAlertaFrete = document.getElementById('vf-pdv-alerta-frete');
    const elAlertaFreteMsg = document.getElementById('vf-pdv-alerta-frete-msg');

    const elCep = document.getElementById('vf-pdv-cep');
    const elRua = document.getElementById('vf-pdv-rua');
    const elNumero = document.getElementById('vf-pdv-numero');
    const elBairro = document.getElementById('vf-pdv-bairro');
    const elCidade = document.getElementById('vf-pdv-cidade');
    const elUf = document.getElementById('vf-pdv-uf');
    const elBtnCalc = document.getElementById('vf-pdv-calc-frete');

    const elForma = document.getElementById('vf-pdv-forma');
    const elBlocoTroco = document.getElementById('vf-pdv-bloco-troco');
    const elBtnFinalizar = document.getElementById('vf-pdv-btn-finalizar');
    const elBtnWa = document.getElementById('vf-pdv-btn-whatsapp');
    const elHiddenItens = document.getElementById('vf-pdv-itens-hidden');

    function brl(n) {
        return 'R$ ' + Number(n).toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+,)/g, '$1.');
    }
    function parseValor(s) {
        if (s == null || s === '') return null;
        const norm = String(s).replace(/[^0-9,\.]/g, '').replace(/\./g, '').replace(',', '.');
        const f = parseFloat(norm);
        return isNaN(f) ? null : f;
    }

    elTabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            elTabs.forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            const canal = tab.getAttribute('data-canal');
            const tipo = tab.getAttribute('data-tipo');
            elCanal.value = canal;
            elTipoEntrega.value = tipo;
            const isWa = canal === '{{ \App\Models\Pedido::CANAL_WHATSAPP }}';
            elBlocoEntrega.classList.toggle('d-none', !isWa);
            elNomeReq.textContent = isWa ? '(obrigatório)' : '(opcional)';
            elTelReq.textContent = isWa ? '(obrigatório)' : '(opcional)';
            elBtnWa.classList.toggle('d-none', !isWa);
            elBtnFinalizar.innerHTML = isWa
                ? '<i class="bi bi-check2-circle me-1"></i> Finalizar (cliente já confirmou)'
                : '<i class="bi bi-check2-circle me-1"></i> Finalizar pedido';
            atualizarTotais();
        });
    });

    function todasOpcoesProduto() {
        return Array.from(elSelectProduto.options).filter(function (op) {
            return op.value !== '';
        });
    }

    function adicionarProdutoPorId(id) {
        const idNum = parseInt(id, 10);
        if (!idNum) return false;
        const op = elSelectProduto.querySelector('option[value="' + idNum + '"]');
        if (!op) return false;
        const nome = op.getAttribute('data-nome') || op.textContent.trim();
        const preco = parseFloat(op.getAttribute('data-preco')) || 0;
        const existente = itens.find(function (i) { return i.produto_id === idNum; });
        if (existente) {
            existente.quantidade += 1;
        } else {
            itens.push({ produto_id: idNum, nome: nome, preco_unitario: preco, quantidade: 1, observacao: '' });
        }
        renderCarrinho();
        return true;
    }

    elBusca.addEventListener('input', function () {
        const q = elBusca.value.trim().toLowerCase();
        let visiveis = 0;
        let primeiroVisivel = '';
        todasOpcoesProduto().forEach(function (op) {
            const nome = (op.getAttribute('data-nome') || '').toLowerCase();
            const ok = q === '' || nome.includes(q);
            op.hidden = !ok;
            op.disabled = !ok;
            if (ok) {
                visiveis++;
                if (primeiroVisivel === '') primeiroVisivel = op.value;
            }
        });
        if (q === '') {
            elBuscaInfo.classList.remove('text-danger');
            elBuscaTotal.textContent = todasOpcoesProduto().length;
            elBuscaInfo.innerHTML = '<span id="vf-pdv-busca-total">' + todasOpcoesProduto().length + '</span> produto(s) disponíveis.';
        } else if (visiveis === 0) {
            elBuscaInfo.classList.add('text-danger');
            elBuscaInfo.textContent = 'Nenhum produto encontrado para "' + q + '".';
        } else {
            elBuscaInfo.classList.remove('text-danger');
            elBuscaInfo.textContent = visiveis + ' produto(s) encontrados para "' + q + '". Selecione e clique em Adicionar.';
        }
        if (elSelectProduto.selectedOptions[0] && elSelectProduto.selectedOptions[0].disabled) {
            elSelectProduto.value = primeiroVisivel || '';
        } else if (elSelectProduto.value === '' && primeiroVisivel !== '') {
            elSelectProduto.value = primeiroVisivel;
        }
    });

    elBtnAdicionar.addEventListener('click', function () {
        const id = elSelectProduto.value;
        if (!id) {
            elBuscaInfo.classList.add('text-danger');
            elBuscaInfo.textContent = 'Selecione um produto na lista antes de adicionar.';
            elSelectProduto.focus();
            return;
        }
        if (adicionarProdutoPorId(id)) {
            elBuscaInfo.classList.remove('text-danger');
            elBuscaInfo.innerHTML = '<i class="bi bi-check-circle text-success me-1"></i>Produto adicionado ao pedido.';
            elBusca.value = '';
            elBusca.dispatchEvent(new Event('input'));
            elSelectProduto.value = '';
            elBusca.focus();
        }
    });

    elSelectProduto.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            elBtnAdicionar.click();
        }
    });

    elBusca.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            elBtnAdicionar.click();
        }
    });

    function renderCarrinho() {
        if (itens.length === 0) {
            elCarrinho.innerHTML = '';
            elCarrinho.appendChild(elVazio);
            elVazio.classList.remove('d-none');
        } else {
            elVazio.classList.add('d-none');
            elCarrinho.innerHTML = '';
            itens.forEach(function (it, idx) {
                const div = document.createElement('div');
                div.className = 'd-flex align-items-center justify-content-between border-bottom py-2';
                div.innerHTML =
                    '<div class="flex-grow-1 me-2">' +
                        '<div class="small fw-semibold">' + it.nome + '</div>' +
                        '<div class="text-muted" style="font-size: 0.75rem;">R$ ' + Number(it.preco_unitario).toFixed(2).replace('.', ',') + ' un</div>' +
                    '</div>' +
                    '<div class="d-flex align-items-center gap-1">' +
                        '<button type="button" class="btn btn-sm btn-outline-secondary px-2" data-act="menos" data-idx="' + idx + '">−</button>' +
                        '<span class="px-2 fw-bold">' + it.quantidade + '</span>' +
                        '<button type="button" class="btn btn-sm btn-outline-secondary px-2" data-act="mais" data-idx="' + idx + '">+</button>' +
                        '<span class="ms-2 fw-bold text-success" style="min-width: 4.5rem; text-align: right;">' + brl(it.preco_unitario * it.quantidade) + '</span>' +
                        '<button type="button" class="btn btn-sm btn-link text-danger px-1" data-act="del" data-idx="' + idx + '"><i class="bi bi-trash"></i></button>' +
                    '</div>';
                elCarrinho.appendChild(div);
            });
        }
        atualizarTotais();
    }

    elCarrinho.addEventListener('click', function (e) {
        const btn = e.target.closest('button[data-act]');
        if (!btn) return;
        const idx = parseInt(btn.getAttribute('data-idx'), 10);
        const act = btn.getAttribute('data-act');
        if (act === 'mais') itens[idx].quantidade += 1;
        else if (act === 'menos') itens[idx].quantidade = Math.max(1, itens[idx].quantidade - 1);
        else if (act === 'del') itens.splice(idx, 1);
        renderCarrinho();
    });

    function subtotal() {
        return itens.reduce(function (s, i) { return s + i.preco_unitario * i.quantidade; }, 0);
    }

    function atualizarTotais() {
        const sub = subtotal();
        elQtd.textContent = itens.reduce(function (s, i) { return s + i.quantidade; }, 0) + ' itens';
        elSub.textContent = brl(sub);
        const isWa = elCanal.value === '{{ \App\Models\Pedido::CANAL_WHATSAPP }}';
        if (isWa) {
            elLinhaFrete.classList.remove('d-none');
            elFrete.textContent = brl(frete.taxa || 0);
        } else {
            elLinhaFrete.classList.add('d-none');
        }
        const total = isWa ? (sub + (frete.taxa || 0)) : sub;
        elTotal.textContent = brl(total);

        if (frete.bloqueada) {
            elAlertaFrete.classList.remove('d-none');
            elAlertaFreteMsg.textContent = 'Endereço fora da área de entrega. Não dá pra finalizar este pedido.';
        } else if (isWa && !frete.calculado && elCep.value.replace(/\D/g, '').length === 8) {
            elAlertaFrete.classList.remove('d-none');
            elAlertaFreteMsg.textContent = 'Clique no botão de calculadora para atualizar o frete antes de finalizar.';
        } else {
            elAlertaFrete.classList.add('d-none');
        }
    }

    elCep.addEventListener('input', function (e) {
        let v = e.target.value.replace(/\D/g, '').slice(0, 8);
        if (v.length > 5) v = v.slice(0, 5) + '-' + v.slice(5);
        e.target.value = v;
        frete.calculado = false;
        elFreteInfo.textContent = 'CEP alterado — clique no botão da calculadora para recalcular.';
        atualizarTotais();
    });

    elForma.addEventListener('change', function () {
        const isDin = elForma.value === '{{ \App\Models\Pedido::PAGAMENTO_DINHEIRO }}';
        elBlocoTroco.classList.toggle('d-none', !isDin);
    });

    elBtnCalc.addEventListener('click', async function () {
        const cep = elCep.value.replace(/\D/g, '');
        if (cep.length !== 8) {
            elFreteInfo.innerHTML = '<span class="text-danger">CEP inválido.</span>';
            return;
        }
        elBtnCalc.disabled = true;
        elBtnCalc.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        try {
            const resp = await fetch('{{ route('empresa.pdv.calcular-frete') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    cep: cep,
                    rua: elRua.value || '',
                    numero: elNumero.value || '',
                    bairro: elBairro.value || '',
                    cidade: elCidade.value || '',
                    estado: elUf.value || '',
                    subtotal: subtotal()
                })
            });
            const json = await resp.json();
            if (!resp.ok || !json.ok) {
                elFreteInfo.innerHTML = '<span class="text-danger">' + (json.message || 'Erro no cálculo.') + '</span>';
                frete = { taxa: 0, rotulo: '', bloqueada: false, calculado: false };
            } else {
                frete = { taxa: parseFloat(json.taxa) || 0, rotulo: json.rotulo || '', bloqueada: !!json.entrega_bloqueada, calculado: true };
                let msg = 'Frete: <strong>' + brl(frete.taxa) + '</strong>';
                if (json.distancia_km != null) msg += ' · ' + Number(json.distancia_km).toFixed(1).replace('.', ',') + ' km';
                if (json.tempo_minutos != null) msg += ' · ~' + json.tempo_minutos + ' min';
                if (json.rotulo) msg += ' — <span class="text-muted">' + json.rotulo + '</span>';
                elFreteInfo.innerHTML = msg;
            }
        } catch (e) {
            elFreteInfo.innerHTML = '<span class="text-danger">Erro de rede. Tente novamente.</span>';
        } finally {
            elBtnCalc.disabled = false;
            elBtnCalc.innerHTML = '<i class="bi bi-calculator"></i>';
            atualizarTotais();
        }
    });

    function preencherHidden() {
        elHiddenItens.innerHTML = '';
        itens.forEach(function (it, idx) {
            const base = 'itens[' + idx + ']';
            const inp1 = document.createElement('input');
            inp1.type = 'hidden';
            inp1.name = base + '[produto_id]';
            inp1.value = it.produto_id;
            const inp2 = document.createElement('input');
            inp2.type = 'hidden';
            inp2.name = base + '[quantidade]';
            inp2.value = it.quantidade;
            const inp3 = document.createElement('input');
            inp3.type = 'hidden';
            inp3.name = base + '[observacao]';
            inp3.value = it.observacao || '';
            elHiddenItens.appendChild(inp1);
            elHiddenItens.appendChild(inp2);
            elHiddenItens.appendChild(inp3);
        });
    }

    elBtnFinalizar.addEventListener('click', function () {
        if (itens.length === 0) {
            alert('Adicione ao menos um produto.');
            return;
        }
        if (elCanal.value === '{{ \App\Models\Pedido::CANAL_WHATSAPP }}') {
            const cep = elCep.value.replace(/\D/g, '');
            if (cep.length !== 8 || !frete.calculado) {
                alert('Calcule o frete antes de finalizar.');
                return;
            }
            if (frete.bloqueada) {
                alert('Endereço fora da área de entrega.');
                return;
            }
        }
        elAcao.value = 'finalizar';
        preencherHidden();
        document.getElementById('vf-pdv-form').submit();
    });

    elBtnWa.addEventListener('click', function () {
        if (itens.length === 0) { alert('Adicione ao menos um produto.'); return; }
        const cep = elCep.value.replace(/\D/g, '');
        if (cep.length !== 8 || !frete.calculado) {
            alert('Calcule o frete antes de salvar.');
            return;
        }
        if (frete.bloqueada) { alert('Endereço fora da área de entrega.'); return; }
        const tel = (document.getElementById('vf-pdv-cliente-tel').value || '').replace(/\D/g, '');
        if (tel.length < 10) {
            if (!confirm('O cliente não tem telefone preenchido. Continuar mesmo assim?')) return;
        }
        elAcao.value = 'whatsapp_confirmar';
        preencherHidden();
        document.getElementById('vf-pdv-form').submit();
    });

    document.querySelector('.vf-pdv-tab.active').click();
})();
</script>
@endpush
