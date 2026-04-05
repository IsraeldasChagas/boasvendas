@extends('layouts.empresa')

@section('title', 'Entregas')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('empresa.dashboard')],
        ['label' => 'Entregas', 'url' => route('empresa.entregas.index')],
    ]])
    <div class="row g-3 mb-3">
        @foreach ([['Em rota',4,'warning'],['Aguardando',2,'secondary'],['Concluídas hoje',18,'success']] as $b)
            <div class="col-md-4">
                <div class="vf-card vf-card-stat">
                    <div>
                        <div class="small text-muted">{{ $b[0] }}</div>
                        <div class="h4 mb-0 fw-bold">{{ $b[1] }}</div>
                    </div>
                    <span class="vf-badge bg-{{ $b[2] }}-subtle text-{{ $b[2] }}">Ao vivo</span>
                </div>
            </div>
        @endforeach
    </div>
    @include('partials.components.filter-bar')
    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 vf-table">
                <thead><tr><th>Pedido</th><th>Entregador</th><th>Região</th><th>Previsão</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <tr>
                        <td>#VF-10411</td>
                        <td>Carlos M.</td>
                        <td>Zona Sul</td>
                        <td class="small">18:35</td>
                        <td><span class="vf-badge bg-warning-subtle text-warning">Em rota</span></td>
                        <td><button type="button" class="btn btn-sm btn-outline-primary" disabled>Rastrear</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
