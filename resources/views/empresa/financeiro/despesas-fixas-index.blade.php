@extends('layouts.empresa')

@section('title', 'Despesas fixas')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Financeiro', 'url' => route('empresa.financeiro.index')],
        ['label' => 'Despesas fixas', 'url' => route('empresa.financeiro.despesas-fixas.index')],
    ]])

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="h5 fw-bold mb-0">Despesas fixas</h2>
            <p class="small text-muted mb-0">Aluguel, salários fixos, internet, gás, energia etc. — referência mensal para gestão.</p>
        </div>
        <a href="{{ route('empresa.financeiro.despesas-fixas.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nova despesa</a>
    </div>

    <div class="vf-card vf-card-stat mb-3">
        <div>
            <div class="small text-muted">Total mensal (ativas)</div>
            <div class="h4 mb-0 fw-bold text-danger">R$ {{ number_format($totalMensal, 2, ',', '.') }}</div>
        </div>
        <i class="bi bi-calendar-month fs-3 text-danger opacity-50"></i>
    </div>

    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 vf-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Categoria</th>
                        @if (\Illuminate\Support\Facades\Schema::hasColumn('financeiro_despesas_fixas', 'vencimento'))
                            <th class="text-nowrap">Vencimento</th>
                        @endif
                        <th class="text-end">Valor mensal (R$)</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($despesas as $d)
                        <tr class="{{ $d->ativo ? '' : 'text-muted' }}">
                            <td class="fw-semibold">{{ $d->nome }}</td>
                            <td class="small">{{ $d->categoria ?: '—' }}</td>
                            @if (\Illuminate\Support\Facades\Schema::hasColumn('financeiro_despesas_fixas', 'vencimento'))
                                <td class="small text-nowrap">{{ $d->vencimento ? $d->vencimento->format('d/m/Y') : '—' }}</td>
                            @endif
                            <td class="text-end">R$ {{ number_format((float) $d->valor_mensal, 2, ',', '.') }}</td>
                            <td><span class="vf-badge {{ $d->ativo ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $d->ativo ? 'Ativa' : 'Inativa' }}</span></td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('empresa.financeiro.despesas-fixas.edit', $d) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                <form action="{{ route('empresa.financeiro.despesas-fixas.destroy', $d) }}" method="post" class="d-inline" onsubmit="return confirm('Excluir esta despesa fixa?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ \Illuminate\Support\Facades\Schema::hasColumn('financeiro_despesas_fixas', 'vencimento') ? 6 : 5 }}" class="text-center text-muted py-5">
                                Nenhuma despesa fixa cadastrada.
                                <a href="{{ route('empresa.financeiro.despesas-fixas.create') }}">Cadastrar</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
