@extends('layouts.admin')

@section('title', $empresa->nome)

@section('content')
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Empresas', 'url' => route('admin.empresas.index')],
        ['label' => $empresa->nome, 'url' => route('admin.empresas.show', $empresa)],
    ]])
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="vf-card p-4 mb-3">
                <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                    <div>
                        <h2 class="h4 fw-bold mb-1">{{ $empresa->nome }}</h2>
                        <p class="text-muted mb-0 small">
                            {{ $empresa->email_contato ?? '—' }}
                            @if ($empresa->cnpj)
                                · CNPJ {{ $empresa->cnpj }}
                            @endif
                        </p>
                    </div>
                    @if ($empresa->status === 'ativa')
                        <span class="vf-badge bg-success-subtle text-success align-self-start">Ativa</span>
                    @elseif ($empresa->status === 'trial')
                        <span class="vf-badge bg-warning-subtle text-warning align-self-start">Trial</span>
                    @else
                        <span class="vf-badge bg-secondary-subtle text-secondary align-self-start">Suspensa</span>
                    @endif
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Plano</label>
                        <div class="fw-semibold">{{ $empresa->plano?->nome ?? '—' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Cliente desde</label>
                        <div>{{ $empresa->cliente_desde ? $empresa->cliente_desde->format('d/m/Y') : '—' }}</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small text-muted">Módulos</label>
                        <div>{{ $empresa->modulos_resumo !== null && $empresa->modulos_resumo !== '' ? $empresa->modulos_resumo : '—' }}</div>
                    </div>
                </div>
            </div>
            <div class="vf-card p-3">
                <h3 class="h6 fw-bold mb-3">Uso (ilustrativo)</h3>
                <div class="vf-chart-fake" style="height:160px">@foreach ([30,45,40,55,50,60,58,65,62,70] as $h)<div class="bar" style="height:{{ $h }}%"></div>@endforeach</div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="vf-card p-3 mb-3">
                <h3 class="h6 fw-bold mb-2">Ações</h3>
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.empresas.edit', $empresa) }}" class="btn btn-outline-primary btn-sm">Editar cadastro / trocar plano</a>
                    <button type="button" class="btn btn-outline-secondary btn-sm" disabled>Suspender</button>
                    <button type="button" class="btn btn-outline-danger btn-sm" disabled>Encerrar</button>
                </div>
                <form action="{{ route('admin.empresas.destroy', $empresa) }}" method="post" class="mt-3"
                      onsubmit="return confirm('Remover esta empresa? Esta ação não pode ser desfeita.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">Excluir empresa</button>
                </form>
            </div>
            <a href="{{ route('admin.empresas.index') }}" class="btn btn-light border w-100">Voltar</a>
        </div>
    </div>
@endsection
