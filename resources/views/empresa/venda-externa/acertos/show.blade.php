@extends('layouts.empresa')

@section('title', 'Acerto #'.$acerto->id)

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Acertos', 'url' => route('empresa.venda-externa.acertos')],
        ['label' => '#'.$acerto->id, 'url' => route('empresa.venda-externa.acertos.show', $acerto)],
    ]])

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="vf-card p-3 mb-3">
                <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Acerto #{{ $acerto->id }}</h2>
                        <div class="small text-muted">
                            Ponto: <strong>{{ $acerto->ponto?->nome ?? '—' }}</strong>
                            @if ($acerto->ponto?->regiao)
                                <span class="text-muted">({{ $acerto->ponto->regiao }})</span>
                            @endif
                        </div>
                        @if ($acerto->remessa)
                            <div class="small mt-2">
                                Remessa:
                                <a href="{{ route('empresa.venda-externa.remessas.show', $acerto->remessa) }}">R-{{ $acerto->remessa->id }}</a>
                                — {{ $acerto->remessa->tituloExibicao() }}
                            </div>
                        @endif
                    </div>
                    <span class="vf-badge {{ $acerto->classeBadgeStatus() }} align-self-start">{{ $acerto->rotuloStatus() }}</span>
                </div>
                <dl class="row small mb-0">
                    <dt class="col-sm-4">Data do acerto</dt>
                    <dd class="col-sm-8">{{ $acerto->data_acerto?->format('d/m/Y') ?? '—' }}</dd>
                    <dt class="col-sm-4">Vendas</dt>
                    <dd class="col-sm-8">{{ $acerto->valor_vendas !== null ? 'R$ '.number_format((float) $acerto->valor_vendas, 2, ',', '.') : '—' }}</dd>
                    <dt class="col-sm-4">Repasse</dt>
                    <dd class="col-sm-8">{{ $acerto->valor_repasse !== null ? 'R$ '.number_format((float) $acerto->valor_repasse, 2, ',', '.') : '—' }}</dd>
                </dl>
                @if ($acerto->observacoes)
                    <div class="mt-3 pt-3 border-top">
                        <div class="small text-muted mb-1">Observações</div>
                        <div class="small">{{ $acerto->observacoes }}</div>
                    </div>
                @endif
            </div>
        </div>
        <div class="col-lg-4">
            <div class="vf-card p-3 mb-3">
                <h3 class="h6 fw-bold mb-3">Ações</h3>
                <div class="d-grid gap-2">
                    <a href="{{ route('empresa.venda-externa.acertos.edit', $acerto) }}" class="btn btn-primary btn-sm">Editar</a>
                    <a href="{{ route('empresa.venda-externa.acertos') }}" class="btn btn-outline-secondary btn-sm">Voltar à lista</a>
                    <form action="{{ route('empresa.venda-externa.acertos.destroy', $acerto) }}" method="post" onsubmit="return confirm('Excluir este acerto?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
