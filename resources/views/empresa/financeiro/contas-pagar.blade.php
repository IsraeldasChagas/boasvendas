@extends('layouts.empresa')

@section('title', 'Contas a pagar')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Financeiro', 'url' => route('empresa.financeiro.index')],
        ['label' => 'Contas a pagar', 'url' => route('empresa.financeiro.contas-pagar')],
    ]])

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 fw-bold mb-0">Contas a pagar</h2>
        <a href="{{ route('empresa.financeiro.contas-pagar.create') }}" class="btn btn-danger btn-sm"><i class="bi bi-plus-lg me-1"></i>Novo título</a>
    </div>

    <form action="{{ route('empresa.financeiro.contas-pagar') }}" method="get" class="vf-filter-bar mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4 col-lg-3">
                <label class="form-label small text-muted mb-1" for="fp-q">Buscar</label>
                <input type="search" class="form-control form-control-sm" id="fp-q" name="q" value="{{ request('q') }}" placeholder="Fornecedor, descrição…">
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small text-muted mb-1" for="fp-sit">Situação</label>
                <select class="form-select form-select-sm" id="fp-sit" name="situacao">
                    <option value="">Todas</option>
                    <option value="aberto" @selected(request('situacao') === 'aberto')>Em aberto</option>
                    <option value="atrasado" @selected(request('situacao') === 'atrasado')>Atrasado</option>
                    <option value="pago" @selected(request('situacao') === 'pago')>Pago</option>
                </select>
            </div>
            <div class="col-md-auto ms-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filtrar</button>
                <a href="{{ route('empresa.financeiro.contas-pagar') }}" class="btn btn-sm btn-outline-secondary">Limpar</a>
            </div>
        </div>
    </form>

    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 vf-table">
                <thead>
                    <tr>
                        <th>Fornecedor</th>
                        <th>Descrição</th>
                        <th>Vencimento</th>
                        <th>Valor</th>
                        <th>Situação</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($titulos as $t)
                        <tr>
                            <td class="fw-medium">{{ $t->contraparte ?: '—' }}</td>
                            <td class="small">{{ $t->descricao }}</td>
                            <td>{{ $t->vencimento->format('d/m/Y') }}</td>
                            <td class="fw-semibold">R$ {{ number_format((float) $t->valor, 2, ',', '.') }}</td>
                            <td><span class="vf-badge {{ $t->classeBadgeSituacao() }}">{{ $t->rotuloSituacao() }}</span></td>
                            <td class="text-end text-nowrap">
                                @if ($t->status === \App\Models\FinanceiroTitulo::STATUS_ABERTO)
                                    <form action="{{ route('empresa.financeiro.contas-pagar.baixar', $t) }}" method="post" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Marcar pago</button>
                                    </form>
                                    <a href="{{ route('empresa.financeiro.contas-pagar.edit', $t) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                                    <form action="{{ route('empresa.financeiro.contas-pagar.destroy', $t) }}" method="post" class="d-inline" onsubmit="return confirm('Excluir este título?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                                    </form>
                                @else
                                    <span class="small text-muted">Pago em {{ $t->pago_em?->format('d/m/Y') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">Nenhum título. <a href="{{ route('empresa.financeiro.contas-pagar.create') }}">Cadastrar</a></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
