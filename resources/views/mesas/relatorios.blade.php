@extends('layouts.empresa')

@section('title', 'Relatórios de mesas')

@section('content')
<form method="get" class="row g-2 align-items-end mb-4">
    <div class="col-auto">
        <label class="form-label">Início</label>
        <input type="date" name="inicio" value="{{ $inicio }}" class="form-control">
    </div>
    <div class="col-auto">
        <label class="form-label">Fim</label>
        <input type="date" name="fim" value="{{ $fim }}" class="form-control">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </div>
</form>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 bg-primary-subtle">
            <div class="card-body">
                <div class="small text-muted">Comandas abertas agora</div>
                <div class="display-6 fw-bold">{{ $mesasAbertas }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 bg-success-subtle">
            <div class="card-body">
                <div class="small text-muted">Comandas fechadas no período</div>
                <div class="display-6 fw-bold">{{ $mesasFechadasPeriodo }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 bg-warning-subtle">
            <div class="card-body">
                <div class="small text-muted">Faturamento (mesas) no período</div>
                <div class="display-6 fw-bold">R$ {{ number_format($totalPeriodo, 2, ',', '.') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Vendas por garçom</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Garçom</th><th>Comandas</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                        @foreach ($porGarcom as $row)
                            <tr>
                                <td>{{ $row->garcom_id ? ($garcons[$row->garcom_id] ?? '#'.$row->garcom_id) : '—' }}</td>
                                <td>{{ $row->qtd }}</td>
                                <td class="text-end">R$ {{ number_format((float) $row->total_valor, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Vendas por mesa</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Mesa</th><th>Comandas</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                        @foreach ($porMesa as $row)
                            <tr>
                                <td>#{{ $row->numero }} {{ $row->nome }}</td>
                                <td>{{ $row->qtd }}</td>
                                <td class="text-end">R$ {{ number_format((float) $row->total_valor, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Produtos mais vendidos (período)</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Produto</th><th class="text-end">Qtd</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                        @forelse ($produtosMaisVendidos as $row)
                            <tr>
                                <td>{{ $row->nome_produto }}</td>
                                <td class="text-end">{{ $row->qtd }}</td>
                                <td class="text-end">R$ {{ number_format((float) $row->total_valor, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted text-center py-3">Sem dados no período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
