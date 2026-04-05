@extends('layouts.empresa')

@section('title', 'Meus chamados')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('empresa.dashboard')],
        ['label' => 'Meus chamados', 'url' => route('empresa.chamados.index')],
    ]])

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h5 fw-bold mb-0">Chamados ao suporte</h1>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="small text-muted">{{ $empresa->nome }}</span>
            <a href="{{ route('empresa.chamados.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Novo chamado</a>
        </div>
    </div>

    <form action="{{ route('empresa.chamados.index') }}" method="get" class="vf-filter-bar mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4 col-lg-3">
                <label class="form-label small text-muted mb-1" for="ch-q">Busca</label>
                <input type="search" class="form-control form-control-sm" id="ch-q" name="q" value="{{ request('q') }}" placeholder="Assunto ou descrição">
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small text-muted mb-1" for="ch-st">Status</label>
                <select class="form-select form-select-sm" id="ch-st" name="status">
                    <option value="">Todos</option>
                    @foreach (\App\Models\SuporteTicket::statusRotulos() as $val => $rotulo)
                        <option value="{{ $val }}" @selected(request('status') === $val)>{{ $rotulo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small text-muted mb-1" for="ch-pr">Prioridade</label>
                <select class="form-select form-select-sm" id="ch-pr" name="prioridade">
                    <option value="">Todas</option>
                    @foreach (\App\Models\SuporteTicket::prioridades() as $val => $rotulo)
                        <option value="{{ $val }}" @selected(request('prioridade') === $val)>{{ $rotulo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filtrar</button>
                <a href="{{ route('empresa.chamados.index') }}" class="btn btn-sm btn-outline-secondary">Limpar</a>
            </div>
        </div>
    </form>

    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 vf-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Assunto</th>
                        <th>Prioridade</th>
                        <th>Status</th>
                        <th class="text-end">Atualizado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $t)
                        @php
                            $pTone = match ($t->prioridade) {
                                'alta' => 'danger',
                                'media' => 'warning',
                                default => 'secondary',
                            };
                            $sTone = match ($t->status) {
                                'aberto' => 'info',
                                'aguardando' => 'secondary',
                                'em_andamento' => 'primary',
                                'resolvido' => 'success',
                                default => 'dark',
                            };
                        @endphp
                        <tr>
                            <td><a href="{{ route('empresa.chamados.show', ['suporteTicket' => $t]) }}" class="fw-semibold text-decoration-none">#{{ $t->id }}</a></td>
                            <td>{{ $t->assunto }}</td>
                            <td><span class="vf-badge bg-{{ $pTone }}-subtle text-{{ $pTone }}">{{ \App\Models\SuporteTicket::prioridades()[$t->prioridade] }}</span></td>
                            <td><span class="vf-badge bg-{{ $sTone }}-subtle text-{{ $sTone }}">{{ \App\Models\SuporteTicket::statusRotulos()[$t->status] }}</span></td>
                            <td class="text-end small">{{ $t->updated_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                @if (request()->hasAny(['q', 'status', 'prioridade']))
                                    Nenhum chamado neste filtro.
                                    <a href="{{ route('empresa.chamados.index') }}" class="d-block mt-2 small">Limpar filtros</a>
                                @else
                                    Nenhum chamado registrado para sua empresa.
                                    <a href="{{ route('empresa.chamados.create') }}" class="d-block mt-2">Abrir um chamado</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($tickets->hasPages())
            <div class="p-3 border-top">{{ $tickets->links() }}</div>
        @endif
    </div>
@endsection
