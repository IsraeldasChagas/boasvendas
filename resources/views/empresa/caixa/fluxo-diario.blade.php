@extends('layouts.empresa')

@section('title', 'Fluxo do dia — caixa')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Caixa', 'url' => route('empresa.caixa.index')],
        ['label' => 'Fluxo do dia', 'url' => route('empresa.caixa.fluxo-diario')],
    ]])

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
            <div class="vf-card vf-card-stat">
                <div>
                    <div class="small text-muted">Entradas no dia</div>
                    <div class="h5 mb-0 text-success">R$ {{ number_format($totalEntradas, 2, ',', '.') }}</div>
                </div>
                <i class="bi bi-plus-circle fs-3 text-success opacity-50"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="vf-card vf-card-stat">
                <div>
                    <div class="small text-muted">Saídas no dia</div>
                    <div class="h5 mb-0 text-danger">R$ {{ number_format($totalSaidas, 2, ',', '.') }}</div>
                </div>
                <i class="bi bi-dash-circle fs-3 text-danger opacity-50"></i>
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
@endsection
