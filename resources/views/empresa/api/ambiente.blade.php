@extends('layouts.empresa')

@section('title', 'API — Ambiente')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('empresa.dashboard')],
        ['label' => 'Configurações', 'url' => route('empresa.configuracoes.index')],
        ['label' => 'API', 'url' => route('empresa.api.status')],
        ['label' => 'Ambiente'],
    ]])

    <h2 class="h5 fw-bold mb-3">Ambiente</h2>
    @include('empresa.api._nav')

    <div class="border rounded-3 p-3 bg-white">
        <dl class="row mb-0">
            <dt class="col-sm-4">APP_ENV</dt>
            <dd class="col-sm-8"><code>{{ $appEnv }}</code></dd>
            <dt class="col-sm-4">Versão da API</dt>
            <dd class="col-sm-8"><code>{{ $apiVersion }}</code></dd>
            <dt class="col-sm-4">Rate limit</dt>
            <dd class="col-sm-8">{{ $rateLimit }} req/min (por token ou IP)</dd>
            <dt class="col-sm-4">Ambientes de token</dt>
            <dd class="col-sm-8">
                @foreach ($environments as $key => $label)
                    <span class="badge text-bg-light border me-1">{{ $label }} <code>{{ $key }}</code></span>
                @endforeach
            </dd>
        </dl>
    </div>

    <p class="text-muted small mt-3 mb-0">
        Use tokens distintos para homologação e produção. A API não amarra integrações a nomes de empresas ou IDs fixos.
    </p>
@endsection
