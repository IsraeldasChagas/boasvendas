@extends('layouts.empresa')

@section('title', 'API — Aplicações')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('empresa.dashboard')],
        ['label' => 'Configurações', 'url' => route('empresa.configuracoes.index')],
        ['label' => 'API', 'url' => route('empresa.api.status')],
        ['label' => 'Aplicações'],
    ]])

    <h2 class="h5 fw-bold mb-3">Aplicações conectadas</h2>
    @include('empresa.api._nav')

    <p class="text-muted">Estrutura preparada para integrações genéricas. Nenhum conector específico por cliente.</p>

    <div class="row g-3">
        @foreach ($integrationTypes as $key => $label)
            <div class="col-md-4">
                <div class="border rounded-3 p-3 bg-white h-100">
                    <div class="fw-semibold">{{ $label }}</div>
                    <div class="text-muted small">Código: <code>{{ $key }}</code></div>
                    <span class="badge text-bg-light border mt-2">Disponível via token genérico</span>
                </div>
            </div>
        @endforeach
    </div>
@endsection
