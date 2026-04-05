@extends('layouts.publico')

@section('title', 'Pedido '.$pedido->codigo_publico)

@section('content')
    <div class="container" style="max-width:640px">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="{{ route('publico.loja', ['slug' => $slug]) }}">Cardápio</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $pedido->codigo_publico }}</li>
            </ol>
        </nav>

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h1 class="h4 fw-bold mb-1">Pedido {{ $pedido->codigo_publico }}</h1>
                <div class="small text-muted">{{ $empresa->nome }} · {{ $pedido->created_at->format('d/m/Y H:i') }}</div>
            </div>
            <span class="vf-badge {{ $pedido->classeBadgeStatus() }}">{{ $pedido->rotuloStatus() }}</span>
        </div>

        @php
            $passos = [
                \App\Models\Pedido::STATUS_RECEBIDO => 'Pedido recebido',
                \App\Models\Pedido::STATUS_PREPARO => 'Em preparo',
                \App\Models\Pedido::STATUS_PRONTO => 'Pronto',
                \App\Models\Pedido::STATUS_ROTA => 'Saiu para entrega',
                \App\Models\Pedido::STATUS_ENTREGUE => 'Entregue',
            ];
            $ordem = array_keys($passos);
            $idxAtual = array_search($pedido->status, $ordem, true);
            if ($pedido->status === \App\Models\Pedido::STATUS_CANCELADO) {
                $idxAtual = false;
            }
        @endphp

        @if ($pedido->status === \App\Models\Pedido::STATUS_CANCELADO)
            <div class="alert alert-secondary small">Este pedido foi cancelado.</div>
        @else
            <div class="vf-card p-3 mb-4">
                <h2 class="h6 fw-bold mb-3">Andamento</h2>
                <ul class="list-unstyled small mb-0">
                    @if ($pedido->status === \App\Models\Pedido::STATUS_ENTREGUE)
                        @foreach ($passos as $rotulo)
                            <li class="mb-2 d-flex align-items-start gap-2">
                                <i class="bi bi-check-circle-fill text-success mt-1"></i>
                                <span>{{ $rotulo }}</span>
                            </li>
                        @endforeach
                    @else
                        @foreach ($passos as $st => $rotulo)
                            @php
                                $i = array_search($st, $ordem, true);
                                $ok = $idxAtual !== false && $i !== false && $i < $idxAtual;
                                $atual = $idxAtual !== false && $i === $idxAtual;
                            @endphp
                            <li class="mb-2 d-flex align-items-start gap-2">
                                @if ($ok)
                                    <i class="bi bi-check-circle-fill text-success mt-1"></i>
                                @elseif ($atual)
                                    <i class="bi bi-arrow-right-circle-fill text-primary mt-1"></i>
                                @else
                                    <i class="bi bi-circle text-muted mt-1"></i>
                                @endif
                                <span class="{{ $atual ? 'fw-semibold' : 'text-muted' }}">{{ $rotulo }}</span>
                            </li>
                        @endforeach
                    @endif
                </ul>
            </div>
        @endif

        <div class="vf-card p-3 mb-3">
            <h2 class="h6 fw-bold mb-2">Itens</h2>
            <ul class="list-unstyled small mb-0">
                @foreach ($pedido->itens as $it)
                    <li class="d-flex justify-content-between py-1 border-bottom">
                        <span>{{ $it->nome_produto }} × {{ $it->quantidade }}</span>
                        <span>R$ {{ number_format((float) $it->subtotal, 2, ',', '.') }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="d-flex justify-content-between small mt-2 text-muted"><span>Subtotal</span><span>R$ {{ number_format((float) $pedido->subtotal, 2, ',', '.') }}</span></div>
            <div class="d-flex justify-content-between small text-muted"><span>Entrega</span><span>R$ {{ number_format((float) $pedido->taxa_entrega, 2, ',', '.') }}</span></div>
            <div class="d-flex justify-content-between fw-bold mt-2"><span>Total</span><span class="text-success">R$ {{ number_format((float) $pedido->total, 2, ',', '.') }}</span></div>
        </div>

        <div class="vf-card p-3 mb-3">
            <h2 class="h6 fw-bold mb-2">Entrega e pagamento</h2>
            <p class="small mb-1"><strong>{{ $pedido->cliente_nome }}</strong> — {{ $pedido->cliente_telefone }}</p>
            @if ($pedido->cliente_email)
                <p class="small text-muted mb-1">{{ $pedido->cliente_email }}</p>
            @endif
            <p class="small mb-0">{{ $pedido->endereco }}@if ($pedido->complemento), {{ $pedido->complemento }}@endif</p>
            <p class="small mt-2 mb-0"><span class="text-muted">Pagamento:</span> {{ $pedido->rotuloFormaPagamento() }}</p>
            @if ($pedido->observacoes)
                <p class="small mt-2 mb-0"><span class="text-muted">Obs.:</span> {{ $pedido->observacoes }}</p>
            @endif
        </div>

        <a href="{{ route('publico.loja', ['slug' => $slug]) }}" class="btn btn-outline-primary">Voltar ao cardápio</a>
    </div>
@endsection
