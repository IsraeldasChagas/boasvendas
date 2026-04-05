@extends('layouts.empresa')

@section('title', 'Fiados')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Venda externa', 'url' => route('empresa.venda-externa.dashboard')],
        ['label' => 'Fiados', 'url' => route('empresa.venda-externa.fiados')],
    ]])

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 fw-bold mb-0">Fiados</h2>
        <a href="{{ route('empresa.venda-externa.fiados.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Novo fiado</a>
    </div>

    <p class="small text-muted mb-3">Controle de valores em aberto por contraparte ou ponto (venda externa).</p>

    <form action="{{ route('empresa.venda-externa.fiados') }}" method="get" class="vf-filter-bar mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3 col-lg-2">
                <label class="form-label small text-muted mb-1" for="fd-st">Status</label>
                <select class="form-select form-select-sm" id="fd-st" name="status">
                    <option value="">Todos</option>
                    @foreach (\App\Models\VeFiado::rotulosStatus() as $val => $rotulo)
                        <option value="{{ $val }}" @selected(request('status') === $val)>{{ $rotulo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-lg-3">
                <label class="form-label small text-muted mb-1" for="fd-pt">Ponto</label>
                <select class="form-select form-select-sm" id="fd-pt" name="ve_ponto_id">
                    <option value="">Todos</option>
                    @foreach ($pontosFiltro as $pt)
                        <option value="{{ $pt->id }}" @selected((string) request('ve_ponto_id') === (string) $pt->id)>{{ $pt->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto ms-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filtrar</button>
                <a href="{{ route('empresa.venda-externa.fiados') }}" class="btn btn-sm btn-outline-secondary">Limpar</a>
            </div>
        </div>
    </form>

    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 vf-table">
                <thead>
                    <tr>
                        <th>Contraparte</th>
                        <th>Ponto</th>
                        <th>Vencimento</th>
                        <th class="text-end">Valor</th>
                        <th>Situação</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($fiados as $f)
                        <tr>
                            <td class="fw-medium small">{{ $f->contraparte ?: '—' }}</td>
                            <td class="small">{{ $f->ponto?->nome ?? '—' }}</td>
                            <td class="small">{{ $f->vencimento?->format('d/m/Y') ?? '—' }}</td>
                            <td class="text-end small @if($f->status === \App\Models\VeFiado::STATUS_ABERTO && $f->situacaoVisual() === 'atrasado') text-danger fw-semibold @endif">
                                R$ {{ number_format((float) $f->valor, 2, ',', '.') }}
                            </td>
                            <td><span class="vf-badge {{ $f->classeBadgeSituacao() }}">{{ $f->rotuloSituacao() }}</span></td>
                            <td><span class="vf-badge {{ $f->classeBadgeStatus() }}">{{ $f->rotuloStatus() }}</span></td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('empresa.venda-externa.fiados.show', $f) }}" class="btn btn-sm btn-outline-primary">Abrir</a>
                                <a href="{{ route('empresa.venda-externa.fiados.edit', $f) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                Nenhum fiado.
                                <a href="{{ route('empresa.venda-externa.fiados.create') }}" class="d-block mt-2">Registrar o primeiro</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
