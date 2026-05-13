@extends('layouts.empresa')

@section('title', 'Calcular frete')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h5 fw-bold mb-1"><i class="bi bi-calculator text-primary me-1"></i>Calcular frete</h1>
            <p class="text-muted small mb-0">Use para responder rápido no WhatsApp/telefone sem montar pedido. Reaproveita as regras de frete da loja.</p>
        </div>
        <div class="text-end small text-muted">
            <div>Modo atual: <strong>{{ $modoRotulo }}</strong></div>
            <div><a href="{{ route('empresa.configuracoes.index') }}">Ajustar configurações de frete</a></div>
        </div>
    </div>

    @if ($modoFrete === \App\Models\Empresa::LOJA_FRETE_GOOGLE_DISTANCIA)
        <div class="alert alert-warning small mb-3">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            Sua loja usa frete por Google Maps. A calculadora rápida não consegue chamar a API do Google direto daqui — para esse modo, simule pelo carrinho da vitrine pública.
            Ainda assim, ela mostra a <strong>taxa padrão</strong> como referência.
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="vf-card border border-primary border-2 shadow-sm overflow-hidden rounded-2">
                <div class="bg-primary-subtle bg-opacity-25 px-4 py-3 border-bottom border-primary border-opacity-25">
                    <h2 class="h6 fw-bold mb-0"><i class="bi bi-geo-alt text-primary me-1"></i>Endereço do cliente</h2>
                </div>
                <div class="p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="vf-fc-cep">CEP do cliente <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" inputmode="numeric" maxlength="9" class="form-control" id="vf-fc-cep" placeholder="00000-000" autofocus>
                            <button type="button" class="btn btn-primary" id="vf-fc-btn-calcular">
                                <i class="bi bi-calculator me-1"></i> Calcular
                            </button>
                        </div>
                        <p class="small text-muted mb-0 mt-1">Mínimo necessário. Endereço completo abaixo só deixa o cálculo mais preciso.</p>
                    </div>

                    <details class="border rounded p-3 mb-3 bg-body-secondary bg-opacity-25">
                        <summary class="fw-semibold text-body small cursor-pointer">+ Endereço completo (opcional)</summary>
                        <div class="row g-2 mt-2">
                            <div class="col-md-8">
                                <label class="form-label small" for="vf-fc-rua">Rua</label>
                                <input type="text" class="form-control form-control-sm" id="vf-fc-rua" placeholder="Rua das Flores">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small" for="vf-fc-numero">Número</label>
                                <input type="text" class="form-control form-control-sm" id="vf-fc-numero" placeholder="123">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small" for="vf-fc-bairro">Bairro</label>
                                <input type="text" class="form-control form-control-sm" id="vf-fc-bairro">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small" for="vf-fc-cidade">Cidade</label>
                                <input type="text" class="form-control form-control-sm" id="vf-fc-cidade">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small" for="vf-fc-estado">UF</label>
                                <input type="text" maxlength="2" class="form-control form-control-sm text-uppercase" id="vf-fc-estado">
                            </div>
                        </div>
                    </details>

                    <div class="mb-2">
                        <label class="form-label small" for="vf-fc-valor">Valor do pedido (opcional)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">R$</span>
                            <input type="text" inputmode="decimal" class="form-control" id="vf-fc-valor" placeholder="Ex.: 90,00">
                        </div>
                        <p class="small text-muted mb-0 mt-1">Usado para detectar frete grátis (se você configurou um valor mínimo de pedido).</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="vf-card border border-success border-2 shadow-sm overflow-hidden rounded-2 h-100">
                <div class="bg-success-subtle bg-opacity-25 px-4 py-3 border-bottom border-success border-opacity-25 d-flex align-items-center justify-content-between">
                    <h2 class="h6 fw-bold mb-0"><i class="bi bi-receipt text-success me-1"></i>Resultado</h2>
                    <span class="badge bg-light text-muted" id="vf-fc-status-badge">Aguardando</span>
                </div>
                <div class="p-4">
                    <div id="vf-fc-placeholder" class="text-muted small text-center py-5">
                        <i class="bi bi-arrow-left-circle fs-1 text-muted d-block mb-2"></i>
                        Digite o CEP e clique em <strong>Calcular</strong> para ver o valor do frete.
                    </div>

                    <div id="vf-fc-resultado" class="d-none">
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="border rounded p-2 text-center">
                                    <div class="small text-muted">Distância</div>
                                    <div class="fw-bold fs-5" id="vf-fc-distancia">—</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2 text-center">
                                    <div class="small text-muted">Tempo</div>
                                    <div class="fw-bold fs-5" id="vf-fc-tempo">—</div>
                                </div>
                            </div>
                        </div>

                        <div class="border-2 border border-success rounded-2 p-3 text-center bg-success-subtle bg-opacity-25 mb-3">
                            <div class="small text-success-emphasis fw-semibold">FRETE</div>
                            <div class="display-6 fw-bold text-success mb-0" id="vf-fc-taxa">R$ 0,00</div>
                        </div>

                        <p class="small text-muted mb-3" id="vf-fc-rotulo">—</p>
                        <p class="small text-muted mb-3 d-none" id="vf-fc-endereco-formatado">—</p>

                        <div class="alert alert-danger small py-2 mb-3 d-none" id="vf-fc-alerta-bloqueada">
                            <i class="bi bi-x-circle-fill me-1"></i>
                            <strong>Cliente fora da área de entrega.</strong> Não aceite este pedido para entrega.
                        </div>

                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-success" id="vf-fc-btn-copiar">
                                <i class="bi bi-clipboard me-1"></i> Copiar mensagem pro WhatsApp
                            </button>
                            <a href="#" class="btn btn-outline-success d-none" id="vf-fc-btn-wa" target="_blank" rel="noopener">
                                <i class="bi bi-whatsapp me-1"></i> Abrir no WhatsApp do cliente
                            </a>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="vf-fc-btn-nova">
                                <i class="bi bi-arrow-clockwise me-1"></i> Nova consulta
                            </button>
                        </div>
                    </div>

                    <div id="vf-fc-erro" class="alert alert-warning small d-none mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <details class="mt-3 small text-muted border rounded px-3 py-2 bg-body-secondary bg-opacity-25">
        <summary class="fw-semibold text-body py-1 user-select-none cursor-pointer">Para enviar pelo WhatsApp já com o telefone do cliente</summary>
        <p class="mb-2 mt-2">Cole o telefone do cliente (com DDD) e o sistema vai abrir o WhatsApp direto na conversa dele com a mensagem pronta.</p>
        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label small" for="vf-fc-tel-cliente">Telefone do cliente</label>
                <input type="text" inputmode="tel" class="form-control form-control-sm" id="vf-fc-tel-cliente" placeholder="(00) 00000-0000">
            </div>
        </div>
    </details>
@endsection

@push('scripts')
<script>
(function () {
    const elCep = document.getElementById('vf-fc-cep');
    const elRua = document.getElementById('vf-fc-rua');
    const elNum = document.getElementById('vf-fc-numero');
    const elBai = document.getElementById('vf-fc-bairro');
    const elCid = document.getElementById('vf-fc-cidade');
    const elUf = document.getElementById('vf-fc-estado');
    const elVal = document.getElementById('vf-fc-valor');
    const elTel = document.getElementById('vf-fc-tel-cliente');

    const elBtn = document.getElementById('vf-fc-btn-calcular');
    const elBtnCopy = document.getElementById('vf-fc-btn-copiar');
    const elBtnWa = document.getElementById('vf-fc-btn-wa');
    const elBtnNova = document.getElementById('vf-fc-btn-nova');

    const elPlaceholder = document.getElementById('vf-fc-placeholder');
    const elResultado = document.getElementById('vf-fc-resultado');
    const elBadge = document.getElementById('vf-fc-status-badge');
    const elDist = document.getElementById('vf-fc-distancia');
    const elTempo = document.getElementById('vf-fc-tempo');
    const elTaxa = document.getElementById('vf-fc-taxa');
    const elRotulo = document.getElementById('vf-fc-rotulo');
    const elEndFmt = document.getElementById('vf-fc-endereco-formatado');
    const elAlertaBloq = document.getElementById('vf-fc-alerta-bloqueada');
    const elErro = document.getElementById('vf-fc-erro');

    elCep.addEventListener('input', function (e) {
        let v = e.target.value.replace(/\D/g, '').slice(0, 8);
        if (v.length > 5) v = v.slice(0, 5) + '-' + v.slice(5);
        e.target.value = v;
    });

    function brl(n) {
        return 'R$ ' + Number(n).toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+,)/g, '$1.');
    }

    function parseValor(s) {
        if (s == null || s === '') return null;
        const norm = String(s).replace(/[^0-9,\.]/g, '').replace(/\./g, '').replace(',', '.');
        const f = parseFloat(norm);
        return isNaN(f) ? null : f;
    }

    function mostrarErro(msg) {
        elErro.textContent = msg;
        elErro.classList.remove('d-none');
        elResultado.classList.add('d-none');
        elPlaceholder.classList.remove('d-none');
        elBadge.textContent = 'Erro';
        elBadge.className = 'badge bg-warning text-dark';
    }

    function limparErro() {
        elErro.classList.add('d-none');
        elErro.textContent = '';
    }

    function montarTextoWhatsApp(dadosUltimos) {
        const linhas = [];
        linhas.push('Olá! Calculei sua entrega:');
        linhas.push('');
        if (dadosUltimos.distancia_km != null) {
            linhas.push('📍 Distância: ' + Number(dadosUltimos.distancia_km).toFixed(1).replace('.', ',') + ' km');
        }
        if (dadosUltimos.tempo_minutos != null) {
            linhas.push('⏱️ Tempo: ~' + dadosUltimos.tempo_minutos + ' min');
        }
        linhas.push('💰 Frete: *' + brl(dadosUltimos.taxa) + '*');
        if (dadosUltimos.rotulo) {
            linhas.push('');
            linhas.push('(' + dadosUltimos.rotulo + ')');
        }
        if (dadosUltimos.entrega_bloqueada) {
            linhas.push('');
            linhas.push('⚠️ Infelizmente este endereço está fora da nossa área de entrega.');
        } else {
            linhas.push('');
            linhas.push('Posso confirmar seu pedido?');
        }
        return linhas.join('\n');
    }

    let ultimo = null;

    async function calcular() {
        limparErro();
        const cep = elCep.value.replace(/\D/g, '');
        if (cep.length !== 8) {
            mostrarErro('Digite um CEP válido com 8 dígitos.');
            return;
        }
        elBtn.disabled = true;
        elBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Calculando...';
        elBadge.textContent = 'Calculando...';
        elBadge.className = 'badge bg-info text-white';

        try {
            const resp = await fetch('{{ route('empresa.frete-calculadora.calcular') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    cep: cep,
                    rua: elRua.value || '',
                    numero: elNum.value || '',
                    bairro: elBai.value || '',
                    cidade: elCid.value || '',
                    estado: elUf.value || '',
                    valor_pedido: parseValor(elVal.value)
                })
            });
            const json = await resp.json();
            if (!resp.ok || !json.ok) {
                mostrarErro(json.message || 'Erro no cálculo.');
                return;
            }
            ultimo = json;
            elPlaceholder.classList.add('d-none');
            elResultado.classList.remove('d-none');
            elDist.textContent = json.distancia_km != null
                ? Number(json.distancia_km).toFixed(1).replace('.', ',') + ' km'
                : '—';
            elTempo.textContent = json.tempo_minutos != null
                ? '~' + json.tempo_minutos + ' min'
                : '—';
            elTaxa.textContent = brl(json.taxa);
            elRotulo.textContent = json.rotulo || '';
            if (json.endereco_formatado) {
                elEndFmt.innerHTML = '<i class="bi bi-geo-alt me-1"></i>' + json.endereco_formatado;
                elEndFmt.classList.remove('d-none');
            } else {
                elEndFmt.classList.add('d-none');
            }
            if (json.entrega_bloqueada) {
                elAlertaBloq.classList.remove('d-none');
                elTaxa.className = 'display-6 fw-bold text-danger mb-0';
                elBadge.textContent = 'Bloqueada';
                elBadge.className = 'badge bg-danger text-white';
            } else {
                elAlertaBloq.classList.add('d-none');
                elTaxa.className = 'display-6 fw-bold text-success mb-0';
                elBadge.textContent = 'OK';
                elBadge.className = 'badge bg-success text-white';
            }

            const tel = (elTel.value || '').replace(/\D/g, '');
            if (tel.length >= 10) {
                let waTel = tel;
                if (waTel.length === 10 || waTel.length === 11) waTel = '55' + waTel;
                const texto = encodeURIComponent(montarTextoWhatsApp(json));
                elBtnWa.href = 'https://wa.me/' + waTel + '?text=' + texto;
                elBtnWa.classList.remove('d-none');
            } else {
                elBtnWa.classList.add('d-none');
            }
        } catch (e) {
            mostrarErro('Não foi possível calcular. Tente novamente.');
        } finally {
            elBtn.disabled = false;
            elBtn.innerHTML = '<i class="bi bi-calculator me-1"></i> Calcular';
        }
    }

    elBtn.addEventListener('click', calcular);
    elCep.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); calcular(); }
    });

    elBtnCopy.addEventListener('click', async function () {
        if (!ultimo) return;
        const texto = montarTextoWhatsApp(ultimo);
        try {
            await navigator.clipboard.writeText(texto);
            const original = elBtnCopy.innerHTML;
            elBtnCopy.innerHTML = '<i class="bi bi-check2 me-1"></i> Copiado!';
            setTimeout(function () { elBtnCopy.innerHTML = original; }, 2000);
        } catch (e) {
            alert('Não foi possível copiar. Selecione e copie manualmente.');
        }
    });

    elBtnNova.addEventListener('click', function () {
        elCep.value = '';
        elRua.value = '';
        elNum.value = '';
        elBai.value = '';
        elCid.value = '';
        elUf.value = '';
        elVal.value = '';
        elPlaceholder.classList.remove('d-none');
        elResultado.classList.add('d-none');
        elBadge.textContent = 'Aguardando';
        elBadge.className = 'badge bg-light text-muted';
        ultimo = null;
        elCep.focus();
    });
})();
</script>
@endpush
