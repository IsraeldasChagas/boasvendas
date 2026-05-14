@extends('layouts.empresa')

@section('title', 'Fiscal — Emitentes')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Fiscal', 'url' => route('empresa.fiscal.dashboard')],
        ['label' => 'Empresas emitentes', 'url' => route('empresa.fiscal.emitentes.index')],
    ]])

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h4 fw-bold mb-0">Empresas emitentes</h2>
        <a href="{{ route('empresa.fiscal.emitentes.create') }}" class="btn btn-primary btn-sm">Novo emitente</a>
    </div>
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 vf-table align-middle">
                <thead>
                    <tr>
                        <th>Razão social</th>
                        <th>CNPJ</th>
                        <th>Ambiente</th>
                        <th>Driver</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($emitentes as $e)
                        <tr>
                            <td class="small fw-medium">{{ $e->razao_social }}</td>
                            <td class="small font-monospace">{{ $e->cnpjMascarado() }}</td>
                            <td class="small">{{ $e->ambiente?->rotulo() ?? '—' }}</td>
                            <td class="small"><code>{{ $e->emissor_tipo }}</code></td>
                            <td>
                                @if ($e->ativo)
                                    <span class="vf-badge bg-success-subtle text-success">Ativo</span>
                                @else
                                    <span class="vf-badge bg-secondary-subtle text-secondary">Inativo</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('empresa.fiscal.emitentes.edit', $e) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhum emitente cadastrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
