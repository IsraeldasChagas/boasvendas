@extends('layouts.empresa')

@section('title', 'Pontos de venda')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Venda externa', 'url' => route('empresa.venda-externa.dashboard')],
        ['label' => 'Pontos', 'url' => route('empresa.venda-externa.pontos')],
    ]])

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 fw-bold mb-0">Pontos / parceiros</h2>
        <a href="{{ route('empresa.venda-externa.pontos.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Novo ponto</a>
    </div>

    <form action="{{ route('empresa.venda-externa.pontos') }}" method="get" class="vf-filter-bar mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4 col-lg-3">
                <label class="form-label small text-muted mb-1" for="vp-q">Buscar</label>
                <input type="search" class="form-control form-control-sm" id="vp-q" name="q" value="{{ request('q') }}" placeholder="Nome, região…">
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small text-muted mb-1" for="vp-st">Status</label>
                <select class="form-select form-select-sm" id="vp-st" name="status">
                    <option value="">Todos</option>
                    <option value="{{ \App\Models\VePonto::STATUS_ATIVO }}" @selected(request('status') === \App\Models\VePonto::STATUS_ATIVO)>Ativo</option>
                    <option value="{{ \App\Models\VePonto::STATUS_PAUSADO }}" @selected(request('status') === \App\Models\VePonto::STATUS_PAUSADO)>Pausado</option>
                </select>
            </div>
            <div class="col-md-auto ms-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filtrar</button>
                <a href="{{ route('empresa.venda-externa.pontos') }}" class="btn btn-sm btn-outline-secondary">Limpar</a>
            </div>
        </div>
    </form>

    <div class="row g-3">
        @forelse ($pontos as $p)
            <div class="col-md-4">
                <div class="vf-card p-3 h-100">
                    <div class="d-flex justify-content-between mb-2 gap-2">
                        <strong class="text-break">{{ $p->nome }}</strong>
                        <span class="vf-badge {{ $p->classeBadgeStatus() }} flex-shrink-0">{{ $p->rotuloStatus() }}</span>
                    </div>
                    <p class="small text-muted mb-2">
                        <i class="bi bi-geo-alt me-1"></i>{{ $p->regiao ?: '—' }}
                    </p>
                    <div class="small text-muted mb-1">
                        @if ($p->proximo_acerto_em)
                            Próximo acerto: <strong>{{ $p->proximo_acerto_em->format('d/m/Y H:i') }}</strong>
                        @else
                            Próximo acerto: <span class="text-muted">não definido</span>
                        @endif
                    </div>
                    <div class="small text-muted mb-3">Último acerto: {{ $p->textoUltimoAcerto() }}</div>
                    <a href="{{ route('empresa.venda-externa.pontos.edit', $p) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="vf-card p-4 text-center text-muted">
                    Nenhum ponto cadastrado.
                    <a href="{{ route('empresa.venda-externa.pontos.create') }}">Adicionar</a>
                </div>
            </div>
        @endforelse
    </div>
@endsection
