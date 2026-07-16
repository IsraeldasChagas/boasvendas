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
        <a href="{{ route('empresa.api.tokens') }}" class="btn btn-primary btn-sm"><i class="bi bi-key me-1"></i>Gerar token</a>
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
                <code class="small d-block text-break">GET /api/v1/integration/status</code>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h3 class="h6 fw-semibold">Como testar</h3>
            <ol class="small mb-3">
                <li>Abra <a href="{{ route('empresa.api.tokens') }}">Tokens</a> e clique em <strong>Gerar token</strong> (ability <code>api.visualizar</code>).</li>
                <li>Copie o token exibido uma única vez.</li>
                <li>Execute o comando abaixo ou use Postman/Insomnia.</li>
            </ol>
            <label class="form-label small text-muted">URL completa</label>
            <input type="text" class="form-control form-control-sm font-monospace mb-2" readonly value="{{ $statusUrl }}">
            <label class="form-label small text-muted">cURL (substitua SEU_TOKEN)</label>
            <pre class="small bg-dark text-light p-3 rounded mb-0"><code>curl -sS \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json" \
  "{{ $statusUrl }}"</code></pre>
        </div>
    </div>

    <div class="alert alert-info mb-0">
        Documentação: <code>docs/API_V1.md</code>. Comando local: <code>php artisan vendaffacil:api-preparar-teste</code>
    </div>
@endsection
