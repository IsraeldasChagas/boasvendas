@extends('layouts.empresa')

@section('title', 'Entrega #'.$remessa->id)

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Entregas', 'url' => route('empresa.venda-externa.remessas.index')],
        ['label' => '#'.$remessa->id, 'url' => route('empresa.venda-externa.remessas.show', $remessa)],
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
                        <h2 class="h5 fw-bold mb-1">{{ $remessa->tituloExibicao() }}</h2>
                        <div class="small text-muted">
                            ID #{{ $remessa->id }}
                            · Criada em {{ $remessa->created_at->format('d/m/Y H:i') }}
                            @if ($remessa->updated_at && ! $remessa->updated_at->eq($remessa->created_at))
                                · Atualizada {{ $remessa->updated_at->format('d/m/Y H:i') }}
                            @endif
                        </div>
                        <div class="small mt-2">
                            <strong>Ponto:</strong> {{ $remessa->ponto?->nome ?? '—' }}
                            @if ($remessa->ponto?->regiao)
                                <span class="text-muted">({{ $remessa->ponto->regiao }})</span>
                            @endif
                        </div>
                    </div>
                    <span class="vf-badge {{ $remessa->classeBadgeStatus() }} align-self-start">{{ $remessa->rotuloStatus() }}</span>
                </div>
                <div class="border rounded p-3 bg-light-subtle">
                    <h3 class="h6 fw-bold mb-2">Itens da entrega</h3>
                    <p class="small text-muted mb-0">Aqui é só a entrega do produto para o parceiro revender. (Produtos/quantidades e acerto serão adicionados numa próxima etapa.) Por enquanto use o título e o status para acompanhar cada entrega.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="vf-card p-3 mb-3">
                <h3 class="h6 fw-bold mb-3">Ações</h3>
                <div class="d-grid gap-2">
                    <a href="{{ route('empresa.venda-externa.remessas.edit', $remessa) }}" class="btn btn-primary btn-sm">Editar entrega</a>
                    <a href="{{ route('empresa.venda-externa.remessas.index') }}" class="btn btn-outline-secondary btn-sm">Voltar à lista</a>
                    <form action="{{ route('empresa.venda-externa.remessas.destroy', $remessa) }}" method="post" onsubmit="return confirm('Excluir esta entrega?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">Excluir entrega</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
