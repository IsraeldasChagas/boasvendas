@extends('layouts.admin')

@section('title', 'Empresas')

@section('content')
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <form action="{{ route('admin.empresas.index') }}" method="get" class="vf-filter-bar mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4 col-lg-3">
                <label class="form-label small text-muted mb-1" for="filtro-q">Buscar</label>
                <input type="search" class="form-control form-control-sm" id="filtro-q" name="q" value="{{ request('q') }}" placeholder="Nome, e-mail, CNPJ…">
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small text-muted mb-1" for="filtro-status">Status</label>
                <select class="form-select form-select-sm" id="filtro-status" name="status">
                    <option value="">Todos</option>
                    @foreach (\App\Models\Empresa::statusRotulos() as $valor => $rotulo)
                        <option value="{{ $valor }}" @selected(request('status') === $valor)>{{ $rotulo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small text-muted mb-1">Período</label>
                <select class="form-select form-select-sm" disabled>
                    <option>Em breve</option>
                </select>
            </div>
            <div class="col-md-2 col-lg-auto ms-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filtrar</button>
                <a href="{{ route('admin.empresas.index') }}" class="btn btn-sm btn-outline-secondary">Limpar</a>
            </div>
        </div>
    </form>

    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('admin.empresas.create') }}" class="btn btn-success btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Nova empresa
        </a>
    </div>

    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 vf-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Empresa</th>
                        <th>Plano</th>
                        <th>Status</th>
                        <th>Módulos</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($empresas as $e)
                        <tr>
                            <td class="small text-muted">#{{ $e->id }}</td>
                            <td class="fw-medium">{{ $e->nome }}</td>
                            <td>
                                @if ($e->plano)
                                    <span class="vf-badge bg-primary-subtle text-primary">{{ $e->plano->nome }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($e->status === 'ativa')
                                    <span class="vf-badge bg-success-subtle text-success">Ativa</span>
                                @elseif ($e->status === 'trial')
                                    <span class="vf-badge bg-warning-subtle text-warning">Trial</span>
                                @else
                                    <span class="vf-badge bg-secondary-subtle text-secondary">Suspensa</span>
                                @endif
                            </td>
                            <td class="small">{{ $e->modulos_resumo !== null && $e->modulos_resumo !== '' ? $e->modulos_resumo : '—' }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.empresas.show', $e) }}" class="btn btn-sm btn-outline-light border-secondary text-dark">Detalhes</a>
                                <a href="{{ route('admin.empresas.edit', $e) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                Nenhuma empresa encontrada.
                                <a href="{{ route('admin.empresas.create') }}">Cadastrar</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
