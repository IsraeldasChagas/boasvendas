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
            <p class="small text-muted mb-0 mt-2">
                <span class="text-body-secondary">Pedido em</span> {{ $pedido->created_at->format('d/m/Y H:i') }}
                <span class="text-muted"> · </span>{{ $pedido->rotuloTipoEntrega() }}
                <span class="text-muted"> · </span>Loja online
            </p>
        </div>

        <div class="vf-card p-3 mb-3">
            <h2 class="h6 fw-bold mb-2">Cliente</h2>
            <p class="mb-1 fw-medium">{{ $pedido->cliente_nome }}</p>
            <p class="small mb-0">
                <a href="tel:{{ preg_replace('/\D+/', '', $pedido->cliente_telefone) }}" class="link-primary text-decoration-none">{{ $pedido->cliente_telefone }}</a>
            </p>
            @if ($pedido->cliente_email)
                <p class="small text-muted mb-0 mt-1">{{ $pedido->cliente_email }}</p>
            @endif
        </div>

        <div class="vf-card p-3 mb-3">
            <h2 class="h6 fw-bold mb-2">Endereço</h2>
            @php
                $linhaEnd = trim((string) ($pedido->endereco ?? ''));
            @endphp
            @if ($linhaEnd !== '')
                <p class="small mb-0">{{ $linhaEnd }}@if ($pedido->complemento), {{ $pedido->complemento }}@endif</p>
            @else
                <p class="small text-warning mb-0">Linha de endereço não registrada neste pedido. Use o CEP abaixo e confira com o cliente.</p>
                @if ($pedido->complemento)
                    <p class="small mb-0 mt-1"><span class="text-muted">Complemento:</span> {{ $pedido->complemento }}</p>
                @endif
            @endif
            @if ($pedido->cep_entrega)
                <p class="small text-muted mb-0 mt-1">CEP {{ substr($pedido->cep_entrega, 0, 5) }}-{{ substr($pedido->cep_entrega, 5) }}</p>
            @endif
        </div>

        <div class="vf-card p-3 mb-3">
            <h2 class="h6 fw-bold mb-2">Itens e valores</h2>
            <p class="small text-muted mb-2">Confira produto, quantidade, extras e observações — igual ao pedido na cozinha/caixa.</p>
            <ul class="list-unstyled small mb-0">
                @foreach ($pedido->itens as $it)
                    <li class="py-2 border-bottom">
                        <div class="d-flex justify-content-between gap-2 align-items-start">
                            <span class="fw-medium">{{ $it->nome_produto }} × {{ $it->quantidade }}</span>
                            <span class="text-nowrap">R$ {{ number_format((float) $it->subtotal, 2, ',', '.') }}</span>
                        </div>
                        @include('partials.opcoes-pedido-item', ['opcoesLinha' => $it->opcoes_linha])
                    </li>
                @endforeach
            </ul>
            <div class="d-flex justify-content-between small mt-3 pt-2 border-top text-muted">
                <span>Subtotal</span>
                <span>R$ {{ number_format((float) $pedido->subtotal, 2, ',', '.') }}</span>
            </div>
            <div class="d-flex justify-content-between small mt-1 text-muted">
                <span>Taxa de entrega</span>
                <span>R$ {{ number_format((float) $pedido->taxa_entrega, 2, ',', '.') }}</span>
            </div>
            <div class="d-flex justify-content-between fw-bold mt-2 pt-2 border-top">
                <span>Total</span>
                <span class="text-success">R$ {{ number_format((float) $pedido->total, 2, ',', '.') }}</span>
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
