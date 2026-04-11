@extends('layouts.empresa')

@section('title', 'Fluxo do dia — caixa')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Caixa', 'url' => route('empresa.caixa.index')],
        ['label' => 'Fluxo do dia', 'url' => route('empresa.caixa.fluxo-diario')],
    ]])

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h2 class="h5 fw-bold mb-1">Fluxo de caixa do dia</h2>
            <p class="small text-muted mb-0">Referência do que entrou e saiu no caixa (movimentos lançados). O <strong>saldo inicial</strong> usa o valor <strong>conferido no último fechamento</strong> antes deste dia (ou o saldo esperado, se não houver conferência).</p>
        </div>
        <form action="{{ route('empresa.caixa.fluxo-diario') }}" method="get" class="d-flex flex-wrap align-items-end gap-2">
            <div>
                <label class="form-label small text-muted mb-0" for="fluxo-data">Data</label>
                <input type="date" class="form-control form-control-sm" id="fluxo-data" name="data" value="{{ $dataRef->format('Y-m-d') }}">
            </div>
            <button type="submit" class="btn btn-sm btn-primary">Ver</button>
        </form>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('empresa.caixa.fluxo-diario', ['data' => $diaAnterior]) }}" class="btn btn-sm btn-outline-secondary">&larr; Dia anterior</a>
        <a href="{{ route('empresa.caixa.fluxo-diario', ['data' => $hojeStr]) }}" class="btn btn-sm btn-outline-secondary">Hoje</a>
        <a href="{{ route('empresa.caixa.fluxo-diario', ['data' => $diaSeguinte]) }}" class="btn btn-sm btn-outline-secondary">Próximo dia &rarr;</a>
        <a href="{{ route('empresa.caixa.index') }}" class="btn btn-sm btn-outline-primary ms-auto">Voltar ao caixa</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="vf-card vf-card-stat">
                <div>
                    <div class="small text-muted">Saldo inicial (ref.)</div>
                    <div class="h5 mb-0 {{ $saldoAnterior >= 0 ? 'text-primary' : 'text-danger' }}">R$ {{ number_format($saldoAnterior, 2, ',', '.') }}</div>
                    @if ($ultimoFechamento)
                        <div class="small text-muted mt-1">Após fech. {{ $ultimoFechamento->fechado_em->format('d/m/Y H:i') }}</div>
                    @else
                        <div class="small text-muted mt-1">Sem fechamento anterior neste histórico</div>
                    @endif
                </div>
                <i class="bi bi-box-arrow-in-down-left fs-3 text-primary opacity-50"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div
                class="vf-card vf-card-stat vf-card-stat--clickable {{ $turnoAberto ? '' : 'vf-card-stat--disabled' }}"
                role="button"
                tabindex="0"
                data-bs-toggle="modal"
                data-bs-target="#modalFluxoLancamento"
                data-lanc-tipo="{{ \App\Models\CaixaMovimento::TIPO_SUPRIMENTO }}"
                data-lanc-titulo="Registrar entrada"
            >
                <div>
                    <div class="small text-muted">Entradas no dia <span class="text-success fw-semibold">+</span></div>
                    <div class="h5 mb-0 text-success">R$ {{ number_format($totalEntradas, 2, ',', '.') }}</div>
                    <div class="small text-muted mt-1">{{ $turnoAberto ? 'Clique para lançar entrada' : 'Caixa fechado — abra para lançar' }}</div>
                </div>
                <i class="bi bi-plus-circle fs-3 text-success opacity-50" aria-hidden="true"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div
                class="vf-card vf-card-stat vf-card-stat--clickable {{ $turnoAberto ? '' : 'vf-card-stat--disabled' }}"
                role="button"
                tabindex="0"
                data-bs-toggle="modal"
                data-bs-target="#modalFluxoLancamento"
                data-lanc-tipo="{{ \App\Models\CaixaMovimento::TIPO_SANGRIA }}"
                data-lanc-titulo="Registrar saída"
            >
                <div>
                    <div class="small text-muted">Saídas no dia <span class="text-danger fw-semibold">−</span></div>
                    <div class="h5 mb-0 text-danger">R$ {{ number_format($totalSaidas, 2, ',', '.') }}</div>
                    <div class="small text-muted mt-1">{{ $turnoAberto ? 'Clique para lançar saída' : 'Caixa fechado — abra para lançar' }}</div>
                </div>
                <i class="bi bi-dash-circle fs-3 text-danger opacity-50" aria-hidden="true"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="vf-card vf-card-stat">
                <div>
                    <div class="small text-muted">Saldo após mov. (aprox.)</div>
                    <div class="h5 mb-0 {{ $saldo >= 0 ? 'text-success' : 'text-danger' }}">R$ {{ number_format($saldo, 2, ',', '.') }}</div>
                </div>
                <i class="bi bi-wallet2 fs-3 text-secondary opacity-50"></i>
            </div>
        </div>
    </div>

    <div class="vf-card p-3 mb-3">
        <h3 class="h6 fw-bold mb-2">Resumo do mês ({{ $dataRef->translatedFormat('F/Y') }})</h3>
        <p class="small text-muted mb-0">Entradas R$ {{ number_format($entradasMes, 2, ',', '.') }} · Saídas R$ {{ number_format($saidasMes, 2, ',', '.') }} · Líquido movimentos R$ {{ number_format($entradasMes - $saidasMes, 2, ',', '.') }}</p>
    </div>

    <div class="vf-card p-0 overflow-hidden mb-3">
        <div class="table-responsive">
            <table class="table table-hover mb-0 vf-table">
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th>Tipo</th>
                        <th>Entrada / Saída</th>
                        <th>Descrição</th>
                        <th class="text-end">Valor</th>
                        <th class="text-end">Saldo após (aprox.)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-light">
                        <td colspan="3" class="small text-muted">—</td>
                        <td class="fw-semibold">Saldo inicial do dia</td>
                        <td class="text-end">—</td>
                        <td class="text-end fw-bold">R$ {{ number_format($saldoAnterior, 2, ',', '.') }}</td>
                    </tr>
                    @forelse ($linhas as $row)
                        <tr>
                            <td class="small text-nowrap">{{ $row['hora']->format('H:i:s') }}</td>
                            <td class="small">{{ $row['tipo'] }}</td>
                            <td>
                                @if ($row['entrada'])
                                    <span class="vf-badge bg-success-subtle text-success">Entrada</span>
                                @else
                                    <span class="vf-badge bg-danger-subtle text-danger">Saída</span>
                                @endif
                            </td>
                            <td class="small">
                                {{ $row['descricao'] }}
                                @if (!empty($row['usuario']))
                                    <span class="text-muted">· {{ $row['usuario'] }}</span>
                                @endif
                            </td>
                            <td class="text-end fw-semibold {{ $row['entrada'] ? 'text-success' : 'text-danger' }}">
                                {{ $row['entrada'] ? '+' : '−' }} R$ {{ number_format($row['valor'], 2, ',', '.') }}
                            </td>
                            <td class="text-end small">R$ {{ number_format($row['saldo_apos'], 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nenhum movimento de caixa neste dia.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($fechamentosNoDia->isNotEmpty())
        <div class="vf-card p-3">
            <h3 class="h6 fw-bold mb-3">Fechamentos registrados neste dia</h3>
            <ul class="list-unstyled small mb-0">
                @foreach ($fechamentosNoDia as $t)
                    <li class="mb-2 pb-2 border-bottom">
                        <strong>Turno #{{ $t->id }}</strong> — {{ $t->fechado_em->format('d/m/Y H:i') }}
                        · Conferido: <strong>R$ {{ number_format((float) ($t->valor_conferido_fechamento ?? 0), 2, ',', '.') }}</strong>
                        · Esperado: R$ {{ number_format($t->saldoEsperado(), 2, ',', '.') }}
                        @if ($t->diferencaFechamento() !== null)
                            @php $dif = $t->diferencaFechamento(); @endphp
                            <span class="text-muted">· Diferença: R$ {{ number_format($dif, 2, ',', '.') }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="modal fade" id="modalFluxoLancamento" tabindex="-1" aria-labelledby="modalFluxoLancamentoTitulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalFluxoLancamentoTitulo">Lançamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    @if ($turnoAberto)
                        @if (! $dataRef->isSameDay(\Carbon\Carbon::today()))
                            <p class="small text-warning mb-3">Você está vendo outro dia: o registro usará a <strong>data e hora atuais</strong> (aparece no fluxo de hoje).</p>
                        @endif
                        <form action="{{ route('empresa.caixa.movimento') }}" method="post" id="formFluxoLancamento">
                            @csrf
                            <input type="hidden" name="lancamento_fluxo" value="1">
                            <input type="hidden" name="tipo" id="modalFluxoLancamentoTipo" value="{{ old('lancamento_fluxo') ? old('tipo') : '' }}">
                            <div class="mb-3">
                                <label class="form-label" for="modalFluxoLancamentoDesc">Descrição</label>
                                <input type="text" class="form-control @error('descricao') is-invalid @enderror" id="modalFluxoLancamentoDesc" name="descricao" value="{{ old('lancamento_fluxo') ? old('descricao') : '' }}" placeholder="Ex.: Troco, retirada…" required>
                                @error('descricao')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="modalFluxoLancamentoValor">Valor (R$)</label>
                                <input type="number" step="0.01" min="0.01" class="form-control @error('valor') is-invalid @enderror" id="modalFluxoLancamentoValor" name="valor" value="{{ old('lancamento_fluxo') ? old('valor') : '' }}" required>
                                @error('valor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            @error('tipo')
                                <div class="alert alert-danger small py-2 mb-3">{{ $message }}</div>
                            @enderror
                            <div class="d-flex gap-2 justify-content-end">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Registrar</button>
                            </div>
                        </form>
                    @else
                        <p class="mb-3">Abra o caixa na <a href="{{ route('empresa.caixa.index') }}">visão geral do caixa</a> para registrar entradas e saídas.</p>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const modalEl = document.getElementById('modalFluxoLancamento');
    if (!modalEl) return;
    const tipoInput = document.getElementById('modalFluxoLancamentoTipo');
    const tituloEl = document.getElementById('modalFluxoLancamentoTitulo');
    const suprimento = @json(\App\Models\CaixaMovimento::TIPO_SUPRIMENTO);
    const sangria = @json(\App\Models\CaixaMovimento::TIPO_SANGRIA);

    function tituloParaTipo(tipo) {
        if (tipo === suprimento) return 'Registrar entrada';
        if (tipo === sangria) return 'Registrar saída';
        return 'Lançamento';
    }

    modalEl.addEventListener('show.bs.modal', function (e) {
        const t = e.relatedTarget;
        if (t && t.dataset && t.dataset.lancTipo && tipoInput && tituloEl) {
            tipoInput.value = t.dataset.lancTipo;
            tituloEl.textContent = t.dataset.lancTitulo || tituloParaTipo(t.dataset.lancTipo);
        }
    });

    document.querySelectorAll('[data-lanc-tipo]').forEach(function (el) {
        el.addEventListener('keydown', function (ev) {
            if (ev.key === 'Enter' || ev.key === ' ') {
                ev.preventDefault();
                el.click();
            }
        });
    });

    @if ($errors->any() && old('lancamento_fluxo'))
    if (typeof bootstrap !== 'undefined' && tipoInput && tituloEl) {
        const tipo = @json(old('tipo'));
        tipoInput.value = tipo || suprimento;
        tituloEl.textContent = tituloParaTipo(tipoInput.value);
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
    @endif
})();
</script>
@endpush
