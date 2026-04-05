@extends('layouts.empresa')

@section('title', 'Relatórios')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('empresa.dashboard')],
        ['label' => 'Relatórios', 'url' => route('empresa.relatorios.index')],
    ]])

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 fw-bold mb-0">Relatórios</h2>
        <button type="button" class="btn btn-outline-secondary btn-sm d-none d-md-inline" onclick="window.print()"><i class="bi bi-printer me-1"></i>Imprimir</button>
    </div>

    <form action="{{ route('empresa.relatorios.index') }}" method="get" class="vf-filter-bar mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-3 col-lg-2">
                <label class="form-label small text-muted mb-1" for="rel-ini">Início</label>
                <input type="date" class="form-control form-control-sm" id="rel-ini" name="inicio" value="{{ $inicio->toDateString() }}" required>
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small text-muted mb-1" for="rel-fim">Fim</label>
                <input type="date" class="form-control form-control-sm" id="rel-fim" name="fim" value="{{ $fim->toDateString() }}" required>
            </div>
            <div class="col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary">Aplicar período</button>
                <a href="{{ route('empresa.relatorios.index') }}" class="btn btn-sm btn-outline-secondary">Últimos 30 dias</a>
            </div>
        </div>
        <p class="small text-muted mb-0 mt-2">Período: {{ $inicio->format('d/m/Y') }} a {{ $fim->format('d/m/Y') }} · intervalo limitado a {{ \App\Http\Controllers\Empresa\RelatorioController::MAX_DIAS_PERIODO }} dias.</p>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="vf-card vf-card-stat h-100">
                <div>
                    <div class="small text-muted">Recebido (financeiro)</div>
                    <div class="h5 mb-0 text-success">R$ {{ number_format($totalRecebidoPeriodo, 2, ',', '.') }}</div>
                    <div class="small text-muted mt-1">Títulos baixados</div>
                </div>
                <div class="icon-wrap bg-success-subtle text-success"><i class="bi bi-graph-up-arrow"></i></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="vf-card vf-card-stat h-100">
                <div>
                    <div class="small text-muted">Pago (financeiro)</div>
                    <div class="h5 mb-0 text-danger">R$ {{ number_format($totalPagoPeriodo, 2, ',', '.') }}</div>
                    <div class="small text-muted mt-1">Saídas baixadas</div>
                </div>
                <div class="icon-wrap bg-danger-subtle text-danger"><i class="bi bi-wallet2"></i></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="vf-card vf-card-stat h-100">
                <div>
                    <div class="small text-muted">Vendas em dinheiro (caixa)</div>
                    <div class="h5 mb-0 text-primary">R$ {{ number_format($totalVendasCaixaPeriodo, 2, ',', '.') }}</div>
                    <div class="small text-muted mt-1">Lançamentos no período</div>
                </div>
                <div class="icon-wrap bg-primary-subtle text-primary"><i class="bi bi-cash-stack"></i></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="vf-card vf-card-stat h-100">
                <div>
                    <div class="small text-muted">Novos clientes</div>
                    <div class="h5 mb-0">{{ $novosClientes }}</div>
                    <div class="small text-muted mt-1">Cadastros no período</div>
                </div>
                <div class="icon-wrap bg-info-subtle text-info"><i class="bi bi-person-plus"></i></div>
            </div>
        </div>
    </div>

    @if ($titulosAtrasados->isNotEmpty())
        <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <strong>Inadimplência / atraso</strong>
                <span class="d-block small">{{ $titulosAtrasados->count() }} título(s) vencido(s) · R$ {{ number_format($valorAtrasado, 2, ',', '.') }}</span>
            </div>
            <a href="{{ route('empresa.financeiro.contas-receber', ['situacao' => 'atrasado']) }}" class="btn btn-sm btn-outline-dark">Ver contas a receber</a>
        </div>
    @endif

    <div class="vf-card p-3 mb-4">
        <h3 class="h6 fw-bold mb-1">Fluxo por dia</h3>
        <p class="small text-muted mb-3">
            <span class="d-inline-block me-3"><span class="d-inline-block rounded me-1" style="width:10px;height:10px;background:#16a34a;"></span> Recebido</span>
            <span class="d-inline-block me-3"><span class="d-inline-block rounded me-1" style="width:10px;height:10px;background:#dc2626;"></span> Pago</span>
            <span class="d-inline-block"><span class="d-inline-block rounded me-1" style="width:10px;height:10px;background:#2563eb;"></span> Venda caixa</span>
        </p>
        @php
            $fluxoDia = array_sum($serieRecebido) + array_sum($seriePago) + array_sum($serieCaixa);
        @endphp
        @if ($fluxoDia <= 0 && count($chartLabels) > 0)
            <p class="text-muted small mb-0 py-4 text-center">Sem movimentos financeiros ou de caixa neste intervalo. Baixe títulos no financeiro ou lance vendas no caixa.</p>
        @else
            <div class="d-flex align-items-end justify-content-between gap-1 overflow-x-auto pb-2" style="min-height: 200px;">
                @foreach ($chartLabels as $i => $label)
                    @php
                        $r = $serieRecebido[$i] ?? 0;
                        $p = $seriePago[$i] ?? 0;
                        $c = $serieCaixa[$i] ?? 0;
                        $hR = $chartMax > 0 && $r > 0 ? max(8, (int) round($r / $chartMax * 100)) : ($r > 0 ? 8 : 2);
                        $hP = $chartMax > 0 && $p > 0 ? max(8, (int) round($p / $chartMax * 100)) : ($p > 0 ? 8 : 2);
                        $hC = $chartMax > 0 && $c > 0 ? max(8, (int) round($c / $chartMax * 100)) : ($c > 0 ? 8 : 2);
                    @endphp
                    <div class="d-flex flex-column align-items-center flex-fill" style="min-width: 18px; max-width: 40px;">
                        <div class="d-flex align-items-end justify-content-center gap-1 flex-grow-1 w-100" style="min-height: 160px;">
                            <div class="rounded-top flex-fill" style="max-width: 8px; height: {{ $hR }}%; background: linear-gradient(180deg,#16a34a,#22c55e); min-height: 3px;" title="R$ {{ number_format($r, 2, ',', '.') }}"></div>
                            <div class="rounded-top flex-fill" style="max-width: 8px; height: {{ $hP }}%; background: linear-gradient(180deg,#dc2626,#f87171); min-height: 3px;" title="R$ {{ number_format($p, 2, ',', '.') }}"></div>
                            <div class="rounded-top flex-fill" style="max-width: 8px; height: {{ $hC }}%; background: linear-gradient(180deg,#2563eb,#60a5fa); min-height: 3px;" title="R$ {{ number_format($c, 2, ',', '.') }}"></div>
                        </div>
                        <div class="small text-muted text-center mt-1" style="font-size: 0.6rem; line-height: 1.1;">{{ $label }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="vf-card p-0 overflow-hidden h-100">
                <div class="p-3 border-bottom">
                    <h3 class="h6 fw-bold mb-0">Estoque baixo (≤ 10)</h3>
                    <p class="small text-muted mb-0">Produtos ativos para reposição</p>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 vf-table">
                        <thead>
                            <tr><th>Produto</th><th class="text-end">Estoque</th><th class="text-end">Preço</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($produtosEstoqueBaixo as $pr)
                                <tr>
                                    <td class="small"><a href="{{ route('empresa.produtos.edit', $pr) }}">{{ $pr->nome }}</a></td>
                                    <td class="text-end fw-semibold {{ $pr->estoque <= 0 ? 'text-danger' : 'text-warning' }}">{{ $pr->estoque }}</td>
                                    <td class="text-end small">R$ {{ number_format((float) $pr->preco, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">Nenhum produto abaixo do limite.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="vf-card p-0 overflow-hidden h-100">
                <div class="p-3 border-bottom">
                    <h3 class="h6 fw-bold mb-0">Títulos em atraso</h3>
                    <p class="small text-muted mb-0">Abertos com vencimento anterior a hoje</p>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 vf-table">
                        <thead>
                            <tr><th>Tipo</th><th>Contraparte</th><th>Venc.</th><th class="text-end">Valor</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($titulosAtrasados as $t)
                                <tr>
                                    <td class="small">{{ $t->tipo === \App\Models\FinanceiroTitulo::TIPO_RECEBER ? 'Receber' : 'Pagar' }}</td>
                                    <td class="small">{{ $t->contraparte ?: '—' }}</td>
                                    <td class="small">{{ $t->vencimento->format('d/m/Y') }}</td>
                                    <td class="text-end small fw-semibold">R$ {{ number_format((float) $t->valor, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Nenhum título atrasado.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <p class="small text-muted mt-4 mb-0">
        Os relatórios usam os módulos <strong>Financeiro</strong> (baixas) e <strong>Caixa</strong> (vendas em dinheiro). Quando pedidos online forem integrados, as vendas podem alimentar estes totais automaticamente.
    </p>
@endsection
