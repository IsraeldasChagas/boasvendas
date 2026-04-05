@extends('layouts.admin')

@section('title', 'Dashboard master')

@section('content')
    <div class="row g-3 mb-4">
        @foreach ($cards as $c)
            <div class="col-6 col-xl-3">
                <div class="vf-card vf-card-stat">
                    <div>
                        <div class="small text-muted">{{ $c['label'] }}</div>
                        <div class="h4 mb-0 fw-bold">{{ $c['value'] }}</div>
                    </div>
                    <div class="icon-wrap bg-{{ $c['tone'] }}-subtle text-{{ $c['tone'] }}"><i class="bi bi-{{ $c['icon'] }}"></i></div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="vf-card p-3">
                <div class="d-flex justify-content-between mb-2">
                    <h2 class="h6 fw-bold mb-0">Novas empresas (últimos 12 meses)</h2>
                    <span class="vf-badge bg-primary-subtle text-primary">Dados reais</span>
                </div>
                <div class="vf-chart-fake">@foreach ($chartHeights as $h)<div class="bar" style="height:{{ max(4, $h) }}%"></div>@endforeach</div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="vf-card p-3 mb-3">
                <h2 class="h6 fw-bold mb-2">Saúde da plataforma</h2>
                <ul class="list-unstyled small mb-0">
                    <li class="d-flex justify-content-between py-2 border-bottom"><span>App</span><span class="vf-badge bg-success-subtle text-success">OK</span></li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span>Fila ({{ $pendingJobs }} jobs)</span>
                        <span class="vf-badge bg-{{ $healthJobs['tone'] }}-subtle text-{{ $healthJobs['tone'] }}">{{ $healthJobs['label'] }}</span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span>Jobs com falha</span>
                        <span class="vf-badge bg-{{ $failedJobs > 0 ? 'danger' : 'success' }}-subtle text-{{ $failedJobs > 0 ? 'danger' : 'success' }}">{{ $failedJobs }}</span>
                    </li>
                    <li class="d-flex justify-content-between py-2">
                        <span>Cobranças (assinaturas)</span>
                        <span class="vf-badge bg-{{ $healthPagamentos['tone'] }}-subtle text-{{ $healthPagamentos['tone'] }}">{{ $healthPagamentos['label'] }}</span>
                    </li>
                </ul>
            </div>
            <a href="{{ route('admin.empresas.index') }}" class="btn btn-primary w-100 btn-sm">Gerenciar empresas</a>
        </div>
    </div>
@endsection
