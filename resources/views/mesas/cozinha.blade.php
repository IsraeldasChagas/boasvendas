@extends('layouts.empresa')

@section('title', 'Painel da cozinha')

@section('content')
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('empresa.mesas.cozinha', ['setor' => 'todos']) }}" class="btn btn-sm {{ ($setorFiltro ?? 'todos') === 'todos' ? 'btn-dark' : 'btn-outline-dark' }}">Todos</a>
    <a href="{{ route('empresa.mesas.cozinha', ['setor' => 'cozinha']) }}" class="btn btn-sm {{ ($setorFiltro ?? '') === 'cozinha' ? 'btn-primary' : 'btn-outline-primary' }}">Cozinha</a>
    <a href="{{ route('empresa.mesas.cozinha', ['setor' => 'bar']) }}" class="btn btn-sm {{ ($setorFiltro ?? '') === 'bar' ? 'btn-info' : 'btn-outline-info' }}">Bar</a>
    <a href="{{ route('empresa.mesas.cozinha', ['setor' => 'caixa']) }}" class="btn btn-sm {{ ($setorFiltro ?? '') === 'caixa' ? 'btn-secondary' : 'btn-outline-secondary' }}">Caixa</a>
</div>

<div class="row g-3">
    @forelse ($itens as $item)
        @php $mesa = $item->comanda?->mesa; @endphp
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 shadow-sm border-0" style="border-left: 4px solid #0d6efd !important;">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <strong class="fs-5">{{ $item->nome_produto }}</strong>
                        <span class="badge bg-secondary">{{ $item->status->label() }}</span>
                    </div>
                    <div class="small text-muted mt-1">
                        Mesa #{{ $mesa?->numero }} · {{ $item->setor_destino->label() }} · Qtd {{ $item->quantidade }}
                    </div>
                    @if ($item->observacao)
                        <div class="alert alert-warning py-2 px-2 small my-2 mb-0">{{ $item->observacao }}</div>
                    @endif
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        @if ($item->status === \App\Enums\Mesas\ComandaItemStatus::Enviado)
                            <form method="post" action="{{ route('empresa.mesas.cozinha.item-status', $item) }}">@csrf
                                <input type="hidden" name="status" value="recebido">
                                <button class="btn btn-outline-primary btn-lg">Recebido</button>
                            </form>
                            <form method="post" action="{{ route('empresa.mesas.cozinha.item-status', $item) }}">@csrf
                                <input type="hidden" name="status" value="em_preparo">
                                <button class="btn btn-primary btn-lg">Em preparo</button>
                            </form>
                        @elseif ($item->status === \App\Enums\Mesas\ComandaItemStatus::Recebido)
                            <form method="post" action="{{ route('empresa.mesas.cozinha.item-status', $item) }}">@csrf
                                <input type="hidden" name="status" value="em_preparo">
                                <button class="btn btn-primary btn-lg">Em preparo</button>
                            </form>
                        @elseif ($item->status === \App\Enums\Mesas\ComandaItemStatus::EmPreparo)
                            <form method="post" action="{{ route('empresa.mesas.cozinha.item-status', $item) }}">@csrf
                                <input type="hidden" name="status" value="pronto">
                                <button class="btn btn-success btn-lg">Pronto</button>
                            </form>
                        @elseif ($item->status === \App\Enums\Mesas\ComandaItemStatus::Pronto)
                            <form method="post" action="{{ route('empresa.mesas.cozinha.item-status', $item) }}">@csrf
                                <input type="hidden" name="status" value="entregue">
                                <button class="btn btn-dark btn-lg">Entregue na mesa</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><p class="text-muted">Nenhum item na fila para o filtro selecionado.</p></div>
    @endforelse
</div>
@endsection
