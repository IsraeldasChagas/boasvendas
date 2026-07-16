@extends('layouts.empresa')

@section('title', 'API')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('empresa.dashboard')],
        ['label' => 'Configurações', 'url' => route('empresa.configuracoes.index')],
        ['label' => 'API'],
    ]])

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="h5 fw-bold mb-0">API — Status</h2>
            <p class="text-muted small mb-0">Base de integração multiempresa (versão {{ $apiVersion }}).</p>
        </div>
    </div>

    @include('empresa.api._nav')

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="border rounded-3 p-3 h-100 bg-white">
                <div class="text-muted small">Tokens ativos</div>
                <div class="fs-4 fw-semibold">{{ $tokensAtivos }}</div>
                <div class="text-muted small">de {{ $tokensTotal }} no total</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded-3 p-3 h-100 bg-white">
                <div class="text-muted small">Logs hoje</div>
                <div class="fs-4 fw-semibold">{{ $logsHoje }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded-3 p-3 h-100 bg-white">
                <div class="text-muted small">Último uso</div>
                <div class="fs-6 fw-semibold">{{ $ultimoUso ? \Illuminate\Support\Carbon::parse($ultimoUso)->timezone(config('app.timezone'))->format('d/m/Y H:i') : '—' }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded-3 p-3 h-100 bg-white">
                <div class="text-muted small">Endpoint de teste</div>
                <code class="small">GET /api/v1/integration/status</code>
            </div>
        </div>
    </div>

    <div class="alert alert-info mb-0">
        <strong>Fase 1.</strong> Infraestrutura pronta. Endpoints de produtos, clientes, pedidos, caixa e fiscal virão nas próximas fases.
        Documentação: <code>docs/API_V1.md</code>.
    </div>
@endsection
