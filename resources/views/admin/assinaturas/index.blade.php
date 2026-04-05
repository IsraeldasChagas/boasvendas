@extends('layouts.admin')

@section('title', 'Assinaturas')

@section('content')
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <form action="{{ route('admin.assinaturas.index') }}" method="get" class="vf-filter-bar mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4 col-lg-3">
                <label class="form-label small text-muted mb-1" for="filtro-q">Buscar</label>
                <input type="search" class="form-control form-control-sm" id="filtro-q" name="q" value="{{ request('q') }}" placeholder="Nome da empresa…">
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small text-muted mb-1" for="filtro-status">Status</label>
                <select class="form-select form-select-sm" id="filtro-status" name="status">
                    <option value="">Todos</option>
                    @foreach (\App\Models\Assinatura::statusRotulos() as $valor => $rotulo)
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
                <a href="{{ route('admin.assinaturas.index') }}" class="btn btn-sm btn-outline-secondary">Limpar</a>
            </div>
        </div>
    </form>

    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('admin.assinaturas.create') }}" class="btn btn-success btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Nova assinatura
        </a>
    </div>

    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 vf-table">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Plano</th>
                        <th>Próxima cobrança</th>
                        <th>Valor</th>
                        <th>Gateway</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assinaturas as $a)
                        <tr>
                            <td class="fw-medium">{{ $a->empresa_nome }}</td>
                            <td>{{ $a->plano?->nome ?? '—' }}</td>
                            <td class="small">{{ $a->proxima_cobranca->format('d/m/Y') }}</td>
                            <td>R$ {{ number_format((float) $a->valor_mensal, 2, ',', '.') }}</td>
                            <td class="small text-muted">{{ $a->gateway !== null && $a->gateway !== '' ? $a->gateway : '—' }}</td>
                            <td>
                                @if ($a->status === 'paga')
                                    <span class="vf-badge bg-success-subtle text-success">Paga</span>
                                @else
                                    <span class="vf-badge bg-warning-subtle text-warning">Pendente</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.assinaturas.edit', $a) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                <form action="{{ route('admin.assinaturas.destroy', $a) }}" method="post" class="d-inline"
                                      onsubmit="return confirm('Remover esta assinatura?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                Nenhuma assinatura encontrada.
                                <a href="{{ route('admin.assinaturas.create') }}">Cadastrar</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
