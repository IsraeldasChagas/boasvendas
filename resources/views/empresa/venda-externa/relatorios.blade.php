@extends('layouts.empresa')

@section('title', 'Relatórios — Venda externa')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Venda externa', 'url' => route('empresa.venda-externa.dashboard')],
        ['label' => 'Relatórios', 'url' => route('empresa.venda-externa.relatorios')],
    ]])

    @php
        $qp = ['inicio' => $inicio->toDateString(), 'fim' => $fim->toDateString()];
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 fw-bold mb-0">Relatórios — venda externa</h2>
        <button type="button" class="btn btn-outline-secondary btn-sm d-none d-md-inline" onclick="window.print()"><i class="bi bi-printer me-1"></i>Imprimir</button>
    </div>

    <form action="{{ route('empresa.venda-externa.relatorios') }}" method="get" class="vf-filter-bar mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-3 col-lg-2">
                <label class="form-label small text-muted mb-1" for="ve-rel-ini">Início</label>
                <input type="date" class="form-control form-control-sm" id="ve-rel-ini" name="inicio" value="{{ $inicio->toDateString() }}" required>
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small text-muted mb-1" for="ve-rel-fim">Fim</label>
                <input type="date" class="form-control form-control-sm" id="ve-rel-fim" name="fim" value="{{ $fim->toDateString() }}" required>
            </div>
            <div class="col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary">Aplicar período</button>
                <a href="{{ route('empresa.venda-externa.relatorios') }}" class="btn btn-sm btn-outline-secondary">Últimos 30 dias</a>
            </div>
        </div>
        <p class="small text-muted mb-0 mt-2">
            Período: {{ $inicio->format('d/m/Y') }} a {{ $fim->format('d/m/Y') }} · intervalo limitado a {{ \App\Http\Controllers\Empresa\VendaExternaController::MAX_DIAS_PERIODO_VE }} dias.
        </p>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="vf-card vf-card-stat h-100">
                <div>
                    <div class="small text-muted">Vendas registradas</div>
                    <div class="h5 mb-0 text-primary">R$ {{ number_format($totalVendasRegistradas, 2, ',', '.') }}</div>
                    <div class="small text-muted mt-1">Lançamentos por data de venda</div>
                </div>
                <div class="icon-wrap bg-primary-subtle text-primary"><i class="bi bi-cart-check"></i></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="vf-card vf-card-stat h-100">
                <div>
                    <div class="small text-muted">Vendas em acertos</div>
                    <div class="h5 mb-0 text-info">R$ {{ number_format($totalVendasDeclaradasAcertos, 2, ',', '.') }}</div>
                    <div class="small text-muted mt-1">Soma de valor em acertos (data do acerto)</div>
                </div>
                <div class="icon-wrap bg-info-subtle text-info"><i class="bi bi-check2-circle"></i></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="vf-card vf-card-stat h-100">
                <div>
                    <div class="small text-muted">Repasse (acertos concluídos)</div>
                    <div class="h5 mb-0 text-success">R$ {{ number_format($totalRepasseAcertos, 2, ',', '.') }}</div>
                    <div class="small text-muted mt-1">No período</div>
                </div>
                <div class="icon-wrap bg-success-subtle text-success"><i class="bi bi-cash-coin"></i></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="vf-card vf-card-stat h-100">
                <div>
                    <div class="small text-muted">Fiado em aberto (hoje)</div>
                    <div class="h5 mb-0 text-danger">R$ {{ number_format($fiadoAbertoSnapshot, 2, ',', '.') }}</div>
                    <div class="small text-muted mt-1">Não filtrado pelo período</div>
                </div>
                <div class="icon-wrap bg-danger-subtle text-danger"><i class="bi bi-journal-text"></i></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-2">
        <div class="col-6 col-md-3">
            <div class="vf-card p-3 h-100">
                <div class="small text-muted">Remessas criadas</div>
                <div class="h4 mb-0 fw-bold">{{ $remessasCriadasPeriodo }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="vf-card p-3 h-100">
                <div class="small text-muted">Remessas encerradas</div>
                <div class="h4 mb-0 fw-bold">{{ $remessasEncerradasPeriodo }}</div>
                <div class="small text-muted mt-1">Atualizadas no período</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="vf-card p-3">
                <h2 class="h6 fw-bold mb-1">Comparativo semanal (12 semanas)</h2>
                <p class="small text-muted mb-2">
                    Barras por semana terminando em <strong>{{ $fim->copy()->endOfWeek()->format('d/m/Y') }}</strong>:
                    <span class="text-success">registros de venda</span> (por data de venda) ×
                    <span style="color:#2563eb;">vendas declaradas em acertos</span> (por data do acerto).
                </p>
                @php $somaR = array_sum($chartSerieRegistros); $somaA = array_sum($chartSerieAcertos); @endphp
                @if ($somaR <= 0 && $somaA <= 0)
                    <p class="text-muted small mb-0 py-4 text-center">Sem dados nessas 12 semanas para montar o gráfico.</p>
                @else
                    <div class="d-flex align-items-end gap-2 px-1" style="height: 220px;">
                        @foreach ($chartLabels as $i => $label)
                            @php
                                $vr = $chartSerieRegistros[$i] ?? 0;
                                $va = $chartSerieAcertos[$i] ?? 0;
                                $hr = $chartMax > 0 && $vr > 0 ? max(8, (int) round($vr / $chartMax * 100)) : ($vr > 0 ? 8 : 0);
                                $ha = $chartMax > 0 && $va > 0 ? max(8, (int) round($va / $chartMax * 100)) : ($va > 0 ? 8 : 0);
                            @endphp
                            <div class="d-flex flex-column align-items-center flex-fill" style="min-width:0;">
                                <div class="d-flex align-items-end justify-content-center gap-1 w-100" style="height: 180px;">
                                    <div class="d-flex flex-column justify-content-end align-items-center flex-fill" style="max-width: 50%;"
                                         title="Registros: R$ {{ number_format($vr, 2, ',', '.') }}">
                                        <div class="w-100 rounded-top" style="height: {{ $hr }}%; min-height: {{ $vr > 0 ? '4px' : '0' }}; background: linear-gradient(180deg,#16a34a,#22c55e); opacity:.9;"></div>
                                    </div>
                                    <div class="d-flex flex-column justify-content-end align-items-center flex-fill" style="max-width: 50%;"
                                         title="Acertos: R$ {{ number_format($va, 2, ',', '.') }}">
                                        <div class="w-100 rounded-top" style="height: {{ $ha }}%; min-height: {{ $va > 0 ? '4px' : '0' }}; background: linear-gradient(180deg,#1d4ed8,#2563eb); opacity:.9;"></div>
                                    </div>
                                </div>
                                <span class="text-muted text-center mt-1" style="font-size: 0.65rem;">{{ $label }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="vf-card p-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h2 class="h6 fw-bold mb-0">Acertos no período</h2>
                    <a href="{{ route('empresa.venda-externa.relatorios.export.acertos', $qp) }}" class="btn btn-sm btn-outline-primary">
                        CSV <i class="bi bi-download"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 vf-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Ponto</th>
                                <th class="text-end">Vendas</th>
                                <th class="text-end">Repasse</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($acertosPeriodo as $a)
                                <tr>
                                    <td class="small">
                                        <a href="{{ route('empresa.venda-externa.acertos.show', $a) }}">{{ $a->data_acerto?->format('d/m/Y') ?? '—' }}</a>
                                    </td>
                                    <td class="small">{{ $a->ponto?->nome ?? '—' }}</td>
                                    <td class="text-end small">{{ $a->valor_vendas !== null ? 'R$ '.number_format((float) $a->valor_vendas, 2, ',', '.') : '—' }}</td>
                                    <td class="text-end small">{{ $a->valor_repasse !== null ? 'R$ '.number_format((float) $a->valor_repasse, 2, ',', '.') : '—' }}</td>
                                    <td><span class="vf-badge {{ $a->classeBadgeStatus() }}">{{ $a->rotuloStatus() }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Nenhum acerto neste período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($acertosPeriodo->count() >= 100)
                    <p class="small text-muted mb-0 mt-2">Mostrando os 100 mais recentes. Use o CSV para a lista completa (até 5.000).</p>
                @endif
            </div>
        </div>
        <div class="col-lg-6">
            <div class="vf-card p-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h2 class="h6 fw-bold mb-0">Fiado em aberto por ponto</h2>
                    <a href="{{ route('empresa.venda-externa.relatorios.export.fiados', $qp) }}" class="btn btn-sm btn-outline-danger">
                        CSV <i class="bi bi-download"></i>
                    </a>
                </div>
                <p class="small text-muted mb-2">Agregado de títulos <strong>em aberto</strong> (situação considera vencimento).</p>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 vf-table">
                        <thead>
                            <tr>
                                <th>Ponto</th>
                                <th class="text-end">Títulos</th>
                                <th class="text-end">Total R$</th>
                                <th class="text-end">Atrasado R$</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($inadimplenciaPorPonto as $row)
                                <tr>
                                    <td class="small">{{ $row->ponto?->nome ?? 'Sem ponto' }}</td>
                                    <td class="text-end small">{{ $row->qtd }}</td>
                                    <td class="text-end small fw-medium">R$ {{ number_format($row->total, 2, ',', '.') }}</td>
                                    <td class="text-end small @if($row->atrasado_valor > 0) text-danger @endif">
                                        R$ {{ number_format($row->atrasado_valor, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Nenhum fiado em aberto.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="vf-card p-3 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="h6 fw-bold mb-0">Movimento de remessas</h2>
                <p class="small text-muted mb-0">Criadas ou atualizadas no período (últimas 50).</p>
            </div>
            <a href="{{ route('empresa.venda-externa.relatorios.export.remessas', $qp) }}" class="btn btn-sm btn-outline-secondary">
                CSV <i class="bi bi-download"></i>
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 vf-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Ponto</th>
                        <th>Status</th>
                        <th>Criada</th>
                        <th>Atualizada</th>
                        <th class="text-end">Dias</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($remessasPeriodo as $r)
                        <tr>
                            <td class="small"><a href="{{ route('empresa.venda-externa.remessas.show', $r) }}">R-{{ $r->id }}</a></td>
                            <td class="small">{{ \Illuminate\Support\Str::limit($r->tituloExibicao(), 40) }}</td>
                            <td class="small">{{ $r->ponto?->nome ?? '—' }}</td>
                            <td><span class="vf-badge {{ $r->classeBadgeStatus() }}">{{ $r->rotuloStatus() }}</span></td>
                            <td class="small">{{ $r->created_at?->format('d/m/Y') }}</td>
                            <td class="small">{{ $r->updated_at?->format('d/m/Y') }}</td>
                            <td class="text-end small">{{ $r->created_at && $r->updated_at ? $r->created_at->diffInDays($r->updated_at) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Nenhuma remessa com movimento neste período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
