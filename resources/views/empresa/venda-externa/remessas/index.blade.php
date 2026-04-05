@extends('layouts.empresa')

@section('title', 'Remessas')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Venda externa', 'url' => route('empresa.venda-externa.dashboard')],
        ['label' => 'Remessas', 'url' => route('empresa.venda-externa.remessas.index')],
    ]])

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 fw-bold mb-0">Remessas / consignação</h2>
        <a href="{{ route('empresa.venda-externa.remessas.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nova remessa</a>
    </div>

    <form action="{{ route('empresa.venda-externa.remessas.index') }}" method="get" class="vf-filter-bar mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3 col-lg-3">
                <label class="form-label small text-muted mb-1" for="rm-q">Buscar título</label>
                <input type="search" class="form-control form-control-sm" id="rm-q" name="q" value="{{ request('q') }}" placeholder="Texto do título…">
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small text-muted mb-1" for="rm-st">Status</label>
                <select class="form-select form-select-sm" id="rm-st" name="status">
                    <option value="">Todos</option>
                    @foreach (\App\Models\VeRemessa::rotulosStatus() as $val => $rotulo)
                        <option value="{{ $val }}" @selected(request('status') === $val)>{{ $rotulo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-lg-3">
                <label class="form-label small text-muted mb-1" for="rm-pt">Ponto</label>
                <select class="form-select form-select-sm" id="rm-pt" name="ve_ponto_id">
                    <option value="">Todos</option>
                    @foreach ($pontosFiltro as $pt)
                        <option value="{{ $pt->id }}" @selected((string) request('ve_ponto_id') === (string) $pt->id)>{{ $pt->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto ms-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filtrar</button>
                <a href="{{ route('empresa.venda-externa.remessas.index') }}" class="btn btn-sm btn-outline-secondary">Limpar</a>
            </div>
        </div>
    </form>

    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 vf-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Ponto</th>
                        <th>Criada em</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($remessas as $r)
                        <tr>
                            <td class="fw-semibold">#{{ $r->id }}</td>
                            <td class="small">{{ $r->tituloExibicao() }}</td>
                            <td class="small">{{ $r->ponto?->nome ?? '—' }}</td>
                            <td class="small">{{ $r->created_at->format('d/m/Y H:i') }}</td>
                            <td><span class="vf-badge {{ $r->classeBadgeStatus() }}">{{ $r->rotuloStatus() }}</span></td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('empresa.venda-externa.remessas.show', $r) }}" class="btn btn-sm btn-outline-primary">Abrir</a>
                                <a href="{{ route('empresa.venda-externa.remessas.edit', $r) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                Nenhuma remessa.
                                <a href="{{ route('empresa.venda-externa.remessas.create') }}">Cadastrar</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
