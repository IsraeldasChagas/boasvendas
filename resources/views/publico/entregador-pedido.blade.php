@extends('layouts.publico')

@section('title', 'Entrega · '.$empresa->nome)

@section('content')
    <div class="container" style="max-width:520px">
        <div class="mb-3">
            <div class="small text-muted">{{ $empresa->nome }}</div>
            <h1 class="h5 fw-bold mb-0">Entrega</h1>
        </div>

        <div class="vf-card p-3 mb-3 border border-2 border-primary-subtle">
            <div class="small text-muted mb-1">Confirme com o cliente</div>
            <div class="fs-3 fw-bold font-monospace text-primary user-select-all">{{ $pedido->codigo_publico }}</div>
        </div>

        <div class="vf-card p-3 mb-3">
            <h2 class="h6 fw-bold mb-2">Cliente</h2>
            <p class="mb-1 fw-medium">{{ $pedido->cliente_nome }}</p>
            <p class="small mb-0">
                <a href="tel:{{ preg_replace('/\D+/', '', $pedido->cliente_telefone) }}" class="link-primary text-decoration-none">{{ $pedido->cliente_telefone }}</a>
            </p>
        </div>

        <div class="vf-card p-3 mb-3">
            <h2 class="h6 fw-bold mb-2">Endereço</h2>
            <p class="small mb-0">{{ $pedido->endereco }}@if ($pedido->complemento), {{ $pedido->complemento }}@endif</p>
            @if ($pedido->cep_entrega)
                <p class="small text-muted mb-0 mt-1">CEP {{ substr($pedido->cep_entrega, 0, 5) }}-{{ substr($pedido->cep_entrega, 5) }}</p>
            @endif
        </div>

        <div class="vf-card p-3 mb-3">
            <h2 class="h6 fw-bold mb-2">Itens</h2>
            <ul class="list-unstyled small mb-0">
                @foreach ($pedido->itens as $it)
                    <li class="py-1 border-bottom">{{ $it->nome_produto }} × {{ $it->quantidade }}</li>
                @endforeach
            </ul>
            <div class="d-flex justify-content-between fw-semibold mt-3 pt-2 border-top small">
                <span>Taxa de entrega</span>
                <span class="text-success text-nowrap">R$ {{ number_format((float) $pedido->taxa_entrega, 2, ',', '.') }}</span>
            </div>
        </div>

        <div class="vf-card p-3 mb-4">
            <h2 class="h6 fw-bold mb-2">Pagamento</h2>
            <p class="small mb-0">{{ $pedido->descricaoPagamentoExibicao() }}</p>
            @if ($pedido->observacoes)
                <p class="small mt-2 mb-0"><span class="text-muted">Obs.:</span> {{ $pedido->observacoes }}</p>
            @endif
        </div>

        <div class="mb-2 d-flex align-items-center justify-content-between gap-2">
            <span class="vf-badge {{ $pedido->classeBadgeStatus() }}">{{ $pedido->rotuloStatus() }}</span>
        </div>

        @if ($pedido->entregadorPodeRegistrarResultado())
            <form action="{{ route('publico.entregador.registrar', ['slug' => $slug, 'codigo' => $pedido->codigo_publico, 'token' => $token]) }}" method="post" class="d-grid gap-2">
                @csrf
                <button type="submit" name="resultado" value="entregue" class="btn btn-success btn-lg">
                    <i class="bi bi-check-circle me-1"></i>Entregue
                </button>
                <button type="submit" name="resultado" value="endereco" class="btn btn-warning">
                    <i class="bi bi-geo-alt me-1"></i>Não encontrei o endereço
                </button>
                <button type="submit" name="resultado" value="cancelado" class="btn btn-outline-danger">
                    <i class="bi bi-x-circle me-1"></i>Cancelado
                </button>
            </form>
            <p class="small text-muted mt-3 mb-0">Ao confirmar, a loja verá o novo status no painel.</p>
        @else
            <div class="alert alert-light border small mb-0">
                Resultado já registrado ou pedido não está em entrega (pronto / em rota).
            </div>
        @endif
    </div>
@endsection
