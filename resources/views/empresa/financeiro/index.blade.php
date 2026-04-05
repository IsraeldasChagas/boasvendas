@extends('layouts.empresa')

@section('title', 'Financeiro')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('empresa.dashboard')],
        ['label' => 'Financeiro', 'url' => route('empresa.financeiro.index')],
    ]])

    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="vf-card vf-card-stat">
                <div>
                    <div class="small text-muted">Saldo do dia</div>
                    <div class="h4 mb-0 fw-bold text-{{ $saldoDia >= 0 ? 'success' : 'danger' }}">R$ {{ number_format($saldoDia, 2, ',', '.') }}</div>
                    <div class="small text-muted mt-1">Recebido hoje − Pago hoje</div>
                </div>
                <i class="bi bi-wallet2 fs-3 text-success opacity-50"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="vf-card vf-card-stat">
                <div>
                    <div class="small text-muted">A receber (aberto)</div>
                    <div class="h4 mb-0 fw-bold text-primary">R$ {{ number_format($aReceber, 2, ',', '.') }}</div>
                </div>
                <i class="bi bi-arrow-down-circle fs-3 text-primary opacity-50"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="vf-card vf-card-stat">
                <div>
                    <div class="small text-muted">A pagar (aberto)</div>
                    <div class="h4 mb-0 fw-bold text-danger">R$ {{ number_format($aPagar, 2, ',', '.') }}</div>
                </div>
                <i class="bi bi-arrow-up-circle fs-3 text-danger opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="vf-card p-3 h-100">
                <h2 class="h6 fw-bold mb-1">Fluxo realizado (últimos 6 meses)</h2>
                <p class="small text-muted mb-3">Barras verdes: recebimentos baixados · Vermelhas: pagamentos baixados</p>
                @php
                    $fluxoTotal = array_sum($chartEntrada) + array_sum($chartSaida);
                @endphp
                @if ($fluxoTotal <= 0)
                    <p class="text-muted small mb-0 py-5 text-center">Ainda não há recebimentos ou pagamentos baixados neste período. Use <strong>Baixar</strong> / <strong>Marcar pago</strong> nos títulos.</p>
                @else
                    <div class="d-flex align-items-end justify-content-between gap-1" style="height: 200px;">
                        @foreach ($chartLabels as $i => $label)
                            @php
                                $hE = $chartMax > 0 ? max(8, (int) round($chartEntrada[$i] / $chartMax * 100)) : 8;
                                $hS = $chartMax > 0 ? max(8, (int) round($chartSaida[$i] / $chartMax * 100)) : 8;
                            @endphp
                            <div class="d-flex flex-column align-items-center flex-fill h-100" style="min-width:0">
                                <div class="d-flex align-items-end justify-content-center gap-1 flex-grow-1 w-100" style="min-height: 0;">
                                    <div class="rounded-top flex-fill" style="max-width: 12px; height: {{ $chartEntrada[$i] > 0 ? $hE : 2 }}%; background: linear-gradient(180deg, #16a34a, #22c55e); min-height: 4px;" title="Recebido: R$ {{ number_format($chartEntrada[$i], 2, ',', '.') }}"></div>
                                    <div class="rounded-top flex-fill" style="max-width: 12px; height: {{ $chartSaida[$i] > 0 ? $hS : 2 }}%; background: linear-gradient(180deg, #dc2626, #f87171); min-height: 4px;" title="Pago: R$ {{ number_format($chartSaida[$i], 2, ',', '.') }}"></div>
                                </div>
                                <div class="small text-muted text-center mt-1 text-truncate w-100" style="font-size: 0.65rem;">{{ $label }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="vf-card p-3">
                <h2 class="h6 fw-bold mb-3">Atalhos</h2>
                <div class="d-grid gap-2">
                    <a href="{{ route('empresa.financeiro.contas-receber') }}" class="btn btn-outline-primary text-start"><i class="bi bi-arrow-down-circle me-2"></i>Contas a receber</a>
                    <a href="{{ route('empresa.financeiro.contas-pagar') }}" class="btn btn-outline-danger text-start"><i class="bi bi-arrow-up-circle me-2"></i>Contas a pagar</a>
                </div>
            </div>
        </div>
    </div>
@endsection
