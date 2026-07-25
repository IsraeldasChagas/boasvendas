@extends('layouts.empresa')

@section('title', 'Fiscal — Dashboard')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Fiscal', 'url' => route('empresa.fiscal.dashboard')],
        ['label' => 'Dashboard', 'url' => route('empresa.fiscal.dashboard')],
    ]])

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h2 class="h4 fw-bold mb-0">Dashboard fiscal</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('empresa.fiscal.emitentes.index') }}" class="btn btn-outline-primary btn-sm">Emitentes fiscais</a>
            <a href="{{ route('empresa.fiscal.configuracoes.edit') }}" class="btn btn-outline-primary btn-sm">Configurações</a>
        </div>
    </div>

    @unless ($temEmitenteCompleto)
        <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <strong>Complete o cadastro do emitente.</strong>
                CPF/CNPJ, inscrições, regime e endereço fiscal são necessários antes de emitir.
            </div>
            <a href="{{ $emitentesAtivos ? route('empresa.fiscal.emitentes.index') : route('empresa.fiscal.emitentes.create') }}"
               class="btn btn-warning btn-sm">Completar cadastro fiscal</a>
        </div>
    @endunless

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="vf-card p-3 h-100">
                <div class="small text-muted">Emitidas hoje</div>
                <div class="h4 fw-bold mb-0">{{ $emitidasHoje }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="vf-card p-3 h-100">
                <div class="small text-muted">Rejeições hoje</div>
                <div class="h4 fw-bold text-danger mb-0">{{ $rejeicoesHoje }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="vf-card p-3 h-100">
                <div class="small text-muted">Faturamento (autorizadas hoje)</div>
                <div class="h4 fw-bold text-success mb-0">R$ {{ number_format($faturamentoHoje, 2, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="vf-card p-3 h-100">
                <div class="small text-muted">Pendentes</div>
                <div class="h4 fw-bold text-warning mb-0">{{ $pendentes }}</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="vf-card p-4">
                <h3 class="h6 fw-bold mb-3">Notas autorizadas — últimos 7 dias</h3>
                <canvas id="vfFiscalChart7d" height="120" aria-label="Gráfico de notas autorizadas" role="img"></canvas>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="vf-card p-4 mb-3">
                <h3 class="h6 fw-bold mb-2">Resumo</h3>
                <p class="small text-muted mb-2">Emitentes ativos: <strong>{{ $emitentesAtivos }}</strong></p>
                <p class="small text-muted mb-0">Módulo: <strong>{{ $config && $config->modulo_ativo ? 'Ativo' : 'Inativo' }}</strong> · Ambiente: <strong>{{ $config?->ambiente?->rotulo() ?? '—' }}</strong></p>
            </div>
            <div class="vf-card p-4">
                <h3 class="h6 fw-bold mb-3">Últimos logs</h3>
                <ul class="list-unstyled small mb-0">
                    @forelse ($ultimosLogs as $lg)
                        <li class="mb-2 pb-2 border-bottom border-light-subtle">
                            <span class="text-muted">{{ $lg->created_at?->format('d/m H:i') }}</span>
                            <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $lg->tipo->value }}</span>
                            <div class="mt-1">{{ \Illuminate\Support\Str::limit($lg->mensagem ?? '—', 120) }}</div>
                        </li>
                    @empty
                        <li class="text-muted">Nenhum log ainda.</li>
                    @endforelse
                </ul>
                <a href="{{ route('empresa.fiscal.logs.index') }}" class="btn btn-link btn-sm px-0 mt-2">Ver todos</a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" crossorigin="anonymous"></script>
    <script>
        (function () {
            var el = document.getElementById('vfFiscalChart7d');
            if (!el || typeof Chart === 'undefined') return;
            var labels = @json(array_column($serie7dias, 'label'));
            var data = @json(array_column($serie7dias, 'autorizadas'));
            new Chart(el, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Autorizadas',
                        data: data,
                        backgroundColor: 'rgba(25, 135, 84, 0.45)',
                        borderRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } }
                    }
                }
            });
        })();
    </script>
@endpush
