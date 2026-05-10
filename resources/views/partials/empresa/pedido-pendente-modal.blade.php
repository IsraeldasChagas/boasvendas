{{-- Só carrega com empresa + menu Pedidos; som/modal só funcionam se loja_confirmar_pedidos estiver ativo (poll retorna enabled). --}}
@push('styles')
<style>
#vfModalPedidoPendente { z-index: 10800; }
body.modal-open:has(#vfModalPedidoPendente.show) .modal-backdrop { z-index: 10790 !important; }
</style>
@endpush

<div class="modal fade vf-pedido-pendente-modal" id="vfModalPedidoPendente" tabindex="-1" aria-labelledby="vfModalPedidoPendenteTitulo" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-danger shadow-lg">
            <div class="modal-header bg-danger-subtle border-danger-subtle">
                <h2 class="modal-title fs-5 fw-bold" id="vfModalPedidoPendenteTitulo">
                    <i class="bi bi-bell-fill text-danger me-2"></i><span id="vf-modal-pendente-titulo-texto">Pedido aguardando confirmação</span>
                </h2>
            </div>
            <div class="modal-body" id="vf-modal-pendente-corpo">
                <p class="text-muted small mb-0">Carregando…</p>
            </div>
            <div class="modal-footer flex-column align-items-stretch gap-2 border-top">
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <a href="#" class="btn btn-outline-secondary btn-sm" id="vf-modal-pendente-abrir-pagina" target="_blank" rel="noopener noreferrer" style="display:none;">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Abrir pedido completo
                    </a>
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-danger" id="vf-modal-btn-recusar" disabled>
                        <i class="bi bi-x-lg me-1"></i>Recusar pedido
                    </button>
                    <button type="button" class="btn btn-success px-4" id="vf-modal-btn-aceitar" disabled>
                        <i class="bi bi-check-lg me-1"></i>Aceitar pedido
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var pollUrl = @json(route('empresa.pedidos.pendentes-poll'));
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var token = csrf ? csrf.getAttribute('content') : '';
    var modalEl = document.getElementById('vfModalPedidoPendente');
    if (!modalEl || !token) return;

    var modal = window.bootstrap ? new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false }) : null;
    if (!modal) return;

    /** Alarme tipo loja/iFood: repete até aceitar ou recusar (pararAlarmePedido). */
    var alarmePedidoTimer = null;
    var INTERVALO_ALARM_SEG = 2.35;

    var atual = null;
    var submitando = false;

    function pararAlarmePedido() {
        if (alarmePedidoTimer) {
            clearInterval(alarmePedidoTimer);
            alarmePedidoTimer = null;
        }
    }

    /** Tom “cozinha/pedido novo”: sequência curta e alta, bem audível. */
    function tocarSomAlarmePedido() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var t = ctx.currentTime;
            var seq = [
                { f: 1046, d: 0.11, off: 0 },
                { f: 784, d: 0.11, off: 0.14 },
                { f: 1318, d: 0.14, off: 0.32 }
            ];
            seq.forEach(function (s) {
                var o = ctx.createOscillator();
                var g = ctx.createGain();
                o.type = 'square';
                o.frequency.value = s.f;
                g.gain.value = 0.055;
                o.connect(g);
                g.connect(ctx.destination);
                var st = t + s.off;
                o.start(st);
                o.stop(st + s.d);
            });
            setTimeout(function () { ctx.close(); }, 900);
        } catch (e) {}
    }

    function iniciarAlarmePedido() {
        pararAlarmePedido();
        function tick() {
            if (modalEl.classList.contains('show') && atual) {
                tocarSomAlarmePedido();
            }
        }
        tick();
        alarmePedidoTimer = setInterval(tick, INTERVALO_ALARM_SEG * 1000);
    }

    function esc(payload) {
        var d = document.createElement('div');
        d.textContent = payload;
        return d.innerHTML;
    }

    function renderCorpo(p) {
        var html = '';
        html += '<p class="small text-muted mb-2">' + esc(String(p.created_at || '')) + ' · ' + esc(String(p.tipo_entrega || '')) + '</p>';
        html += '<p class="fs-4 fw-bold mb-1">' + esc(String(p.codigo_publico || '')) + '</p>';
        html += '<p class="mb-2"><strong>Cliente:</strong> ' + esc(String(p.cliente_nome || '')) + '</p>';
        html += '<p class="mb-3"><strong>Total:</strong> <span class="text-success fw-bold">' + esc(String(p.total_fmt || '')) + '</span></p>';
        html += '<h3 class="h6 fw-bold border-bottom pb-2 mb-2">Itens</h3><ul class="list-unstyled small mb-0">';
        (p.itens || []).forEach(function (it) {
            html += '<li class="py-1 border-bottom d-flex justify-content-between gap-2"><span>' + esc(it.nome) + '</span><span>× ' + esc(String(it.qtd)) + '</span></li>';
        });
        html += '</ul>';
        document.getElementById('vf-modal-pendente-corpo').innerHTML = html;
        document.getElementById('vf-modal-pendente-titulo-texto').textContent = 'Confirmar: ' + (p.codigo_publico || '');

        var abrir = document.getElementById('vf-modal-pendente-abrir-pagina');
        if (p.show_url) {
            abrir.href = p.show_url;
            abrir.style.display = '';
        } else {
            abrir.style.display = 'none';
        }

        document.getElementById('vf-modal-btn-aceitar').disabled = false;
        document.getElementById('vf-modal-btn-recusar').disabled = false;
        atual = p;
    }

    async function postDecisao(url, decisao) {
        var fd = new FormData();
        fd.append('decisao', decisao);
        fd.append('_token', token);
        var r = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: fd,
            credentials: 'same-origin'
        });
        var j = await r.json().catch(function () { return {}; });
        if (!r.ok) {
            var errMsg = j.message;
            if (!errMsg && j.errors) {
                errMsg = Object.values(j.errors).flat().join(' ');
            }
            throw new Error(errMsg || 'Não foi possível atualizar o pedido.');
        }
        if (!j.ok) {
            throw new Error(j.message || 'Não foi possível atualizar o pedido.');
        }
        return j;
    }

    document.getElementById('vf-modal-btn-aceitar').addEventListener('click', function () {
        if (!atual || submitando) return;
        pararAlarmePedido();
        submitando = true;
        document.getElementById('vf-modal-btn-aceitar').disabled = true;
        document.getElementById('vf-modal-btn-recusar').disabled = true;
        postDecisao(atual.pendente_post_url, 'aceitar').then(function (j) {
            if (j.proximo) {
                renderCorpo(j.proximo);
                iniciarAlarmePedido();
            } else {
                modal.hide();
                atual = null;
            }
        }).catch(function (err) {
            alert(err.message || 'Erro ao aceitar.');
            if (atual) renderCorpo(atual);
            if (modalEl.classList.contains('show') && atual) iniciarAlarmePedido();
        }).finally(function () {
            submitando = false;
        });
    });

    document.getElementById('vf-modal-btn-recusar').addEventListener('click', function () {
        if (!atual || submitando) return;
        if (!confirm('Recusar este pedido? O cliente verá como cancelado e o estoque volta.')) return;
        pararAlarmePedido();
        submitando = true;
        document.getElementById('vf-modal-btn-aceitar').disabled = true;
        document.getElementById('vf-modal-btn-recusar').disabled = true;
        postDecisao(atual.pendente_post_url, 'recusar').then(function (j) {
            if (j.proximo) {
                renderCorpo(j.proximo);
                iniciarAlarmePedido();
            } else {
                modal.hide();
                atual = null;
            }
        }).catch(function (err) {
            alert(err.message || 'Erro ao recusar.');
            if (atual) renderCorpo(atual);
            if (modalEl.classList.contains('show') && atual) iniciarAlarmePedido();
        }).finally(function () {
            submitando = false;
        });
    });

    function processarPoll(data) {
        var lista = data.pedidos || [];
        if (!data.enabled || lista.length === 0) {
            pararAlarmePedido();
            if (modalEl.classList.contains('show')) {
                modal.hide();
            }
            atual = null;
            return;
        }

        var primeiro = lista[0];
        if (!modalEl.classList.contains('show')) {
            renderCorpo(primeiro);
            modal.show();
            return;
        }
        if (!atual || atual.id !== primeiro.id) {
            renderCorpo(primeiro);
            iniciarAlarmePedido();
        }
    }

    function poll() {
        fetch(pollUrl, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).then(processarPoll).catch(function () {});
    }

    modalEl.addEventListener('shown.bs.modal', function () {
        if (atual) {
            iniciarAlarmePedido();
        }
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        pararAlarmePedido();
    });

    poll();
    setInterval(poll, 10000);
})();
</script>
@endpush
