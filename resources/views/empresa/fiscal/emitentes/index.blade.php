@extends('layouts.empresa')

@section('title', 'Fiscal — Emitentes')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Fiscal', 'url' => route('empresa.fiscal.dashboard')],
        ['label' => 'Emitentes fiscais', 'url' => route('empresa.fiscal.emitentes.index')],
    ]])

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="h4 fw-bold mb-1">Emitentes fiscais</h2>
            <p class="small text-muted mb-0">Cadastro da pessoa jurídica ou física responsável pela nota.</p>
        </div>
        <a href="{{ route('empresa.fiscal.emitentes.create') }}" class="btn btn-primary btn-sm">Cadastrar emitente</a>
    </div>
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 vf-table align-middle">
                <thead>
                    <tr>
                        <th>Emitente</th>
                        <th>Tipo</th>
                        <th>CPF/CNPJ</th>
                        <th>Ambiente</th>
                        <th>Driver</th>
                        <th>Cadastro</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($emitentes as $e)
                        <tr>
                            <td>
                                <div class="small fw-medium">{{ $e->razao_social }}</div>
                                <div class="text-muted" style="font-size:.75rem">{{ $e->municipio ?: 'Município não informado' }}{{ $e->uf ? '/'.$e->uf : '' }}</div>
                            </td>
                            <td class="small"><span class="badge text-bg-light border text-uppercase">{{ $e->tipo_pessoa?->value ?? 'PJ' }}</span></td>
                            <td class="small font-monospace">{{ $e->documentoMascarado() }}</td>
                            <td class="small">{{ $e->ambiente?->rotulo() ?? '—' }}</td>
                            <td class="small"><code>{{ $e->emissor_tipo }}</code></td>
                            <td>
                                @if ($e->cadastroFiscalCompleto())
                                    <span class="vf-badge bg-success-subtle text-success">Completo</span>
                                @else
                                    <span class="vf-badge bg-warning-subtle text-warning-emphasis">Completar</span>
                                @endif
                                @unless ($e->ativo)<span class="vf-badge bg-secondary-subtle text-secondary">Inativo</span>@endunless
                            </td>
                            <td class="text-end">
                                <a href="{{ route('empresa.fiscal.emitentes.edit', $e) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Nenhum emitente fiscal cadastrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
