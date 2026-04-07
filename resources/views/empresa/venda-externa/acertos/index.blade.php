@extends('layouts.empresa')

@section('title', 'Acertos')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Venda externa', 'url' => route('empresa.venda-externa.dashboard')],
        ['label' => 'Acertos', 'url' => route('empresa.venda-externa.acertos')],
    ]])

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 fw-bold mb-0">Acertos</h2>
        <a href="{{ route('empresa.venda-externa.acertos.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Novo acerto</a>
    </div>

    <form action="{{ route('empresa.venda-externa.acertos') }}" method="get" class="vf-filter-bar mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3 col-lg-2">
                <label class="form-label small text-muted mb-1" for="ac-st">Status</label>
                <select class="form-select form-select-sm" id="ac-st" name="status">
                    <option value="{{ \App\Models\VeAcerto::STATUS_ABERTO }}" @selected($statusFiltro === \App\Models\VeAcerto::STATUS_ABERTO)>Não acertado</option>
                    <option value="{{ \App\Models\VeAcerto::STATUS_CONCLUIDO }}" @selected($statusFiltro === \App\Models\VeAcerto::STATUS_CONCLUIDO)>Acertado</option>
                </select>
            </div>
            <div class="col-md-4 col-lg-3">
                <label class="form-label small text-muted mb-1" for="ac-pt">Parceiro</label>
                <select class="form-select form-select-sm" id="ac-pt" name="ve_ponto_id">
                    <option value="">Todos</option>
                    @foreach ($pontosFiltro as $pt)
                        <option value="{{ $pt->id }}" @selected((string) request('ve_ponto_id') === (string) $pt->id)>{{ $pt->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto ms-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filtrar</button>
                <a href="{{ route('empresa.venda-externa.acertos') }}" class="btn btn-sm btn-outline-secondary">Limpar</a>
            </div>
        </div>
    </form>

    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 vf-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Parceiro</th>
                        <th>Remessa</th>
                        <th class="text-end">Vendas</th>
                        <th class="text-end">Repasse</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($acertos as $a)
                        <tr>
                            <td class="small">{{ $a->data_acerto?->format('d/m/Y') ?? '—' }}</td>
                            <td class="small">{{ $a->ponto?->nome ?? '—' }}</td>
                            <td class="small">
                                @if ($a->remessa)
                                    <a href="{{ route('empresa.venda-externa.remessas.show', $a->remessa) }}">R-{{ $a->remessa->id }}</a>
                                    <span class="text-muted d-block" style="font-size: 0.7rem;">{{ \Illuminate\Support\Str::limit($a->remessa->tituloExibicao(), 28) }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end small">
                                @if ($a->valor_vendas !== null)
                                    R$ {{ number_format((float) $a->valor_vendas, 2, ',', '.') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end small">
                                @if ($a->valor_repasse !== null)
                                    R$ {{ number_format((float) $a->valor_repasse, 2, ',', '.') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td><span class="vf-badge {{ $a->classeBadgeStatus() }}">{{ $a->status === \App\Models\VeAcerto::STATUS_CONCLUIDO ? 'Acertado' : 'Não acertado' }}</span></td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('empresa.venda-externa.acertos.show', $a) }}" class="btn btn-sm btn-outline-primary">Abrir</a>
                                <a href="{{ route('empresa.venda-externa.acertos.edit', $a) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                Nenhum acerto.
                                <a href="{{ route('empresa.venda-externa.acertos.create') }}">Registrar</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
