@extends('layouts.empresa')

@section('title', 'Fiscal — Certificados')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Fiscal', 'url' => route('empresa.fiscal.dashboard')],
        ['label' => 'Certificados', 'url' => route('empresa.fiscal.certificados.index')],
    ]])

    <h2 class="h4 fw-bold mb-2">Certificados digitais</h2>
    <p class="text-muted small mb-4">Resumo por emitente. Senhas e tokens são gravados cifrados no banco; não são exibidos aqui.</p>

    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 vf-table align-middle small">
                <thead>
                    <tr>
                        <th>Emitente</th>
                        <th>CNPJ</th>
                        <th>Certificado (path)</th>
                        <th>Senha</th>
                        <th class="text-end">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($emitentes as $e)
                        <tr>
                            <td class="fw-medium">{{ $e->razao_social }}</td>
                            <td class="font-monospace">{{ $e->cnpjMascarado() }}</td>
                            <td class="font-monospace text-break">{{ $e->certificado_path ?: '—' }}</td>
                            <td><span class="text-muted small">Definir na edição</span></td>
                            <td class="text-end"><a href="{{ route('empresa.fiscal.emitentes.edit', $e) }}" class="btn btn-sm btn-outline-primary">Editar</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Nenhum emitente.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
