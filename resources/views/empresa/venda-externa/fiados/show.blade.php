@extends('layouts.empresa')

@section('title', 'Fiado #'.$fiado->id)

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Fiados', 'url' => route('empresa.venda-externa.fiados')],
        ['label' => '#'.$fiado->id, 'url' => route('empresa.venda-externa.fiados.show', $fiado)],
    ]])

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="vf-card p-3 mb-3">
                <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Fiado #{{ $fiado->id }}</h2>
                        <div class="small text-muted">
                            Contraparte: <strong>{{ $fiado->contraparte ?: '—' }}</strong>
                        </div>
                        @if ($fiado->ponto)
                            <div class="small mt-2">
                                Ponto:
                                <a href="{{ route('empresa.venda-externa.pontos.edit', $fiado->ponto) }}">{{ $fiado->ponto->nome }}</a>
                            </div>
                        @endif
                    </div>
                    <div class="d-flex flex-column align-items-end gap-1">
                        <span class="vf-badge {{ $fiado->classeBadgeStatus() }}">{{ $fiado->rotuloStatus() }}</span>
                        <span class="vf-badge {{ $fiado->classeBadgeSituacao() }}">{{ $fiado->rotuloSituacao() }}</span>
                    </div>
                </div>
                <dl class="row small mb-0">
                    <dt class="col-sm-4">Valor</dt>
                    <dd class="col-sm-8 fw-semibold">R$ {{ number_format((float) $fiado->valor, 2, ',', '.') }}</dd>
                    <dt class="col-sm-4">Vencimento</dt>
                    <dd class="col-sm-8">{{ $fiado->vencimento?->format('d/m/Y') ?? '—' }}</dd>
                    <dt class="col-sm-4">Registrado em</dt>
                    <dd class="col-sm-8">{{ $fiado->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                </dl>
                @if ($fiado->descricao)
                    <div class="mt-3 pt-3 border-top">
                        <div class="small text-muted mb-1">Descrição</div>
                        <div class="small">{{ $fiado->descricao }}</div>
                    </div>
                @endif
            </div>
        </div>
        <div class="col-lg-4">
            <div class="vf-card p-3 mb-3">
                <h3 class="h6 fw-bold mb-3">Ações</h3>
                <div class="d-grid gap-2">
                    @if ($fiado->podeDarBaixa())
                        <form action="{{ route('empresa.venda-externa.fiados.baixar', $fiado) }}" method="post" onsubmit="return confirm('Marcar este fiado como quitado?');">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm w-100">Dar baixa (quitar)</button>
                        </form>
                    @endif
                    <a href="{{ route('empresa.venda-externa.fiados.edit', $fiado) }}" class="btn btn-primary btn-sm">Editar</a>
                    <a href="{{ route('empresa.venda-externa.fiados') }}" class="btn btn-outline-secondary btn-sm">Voltar à lista</a>
                    <form action="{{ route('empresa.venda-externa.fiados.destroy', $fiado) }}" method="post" onsubmit="return confirm('Excluir este fiado?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
