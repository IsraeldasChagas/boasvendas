@extends('layouts.publico')

@section('title', 'Fidelidade — '.$empresa->nome)

@section('content')
    <div class="container" style="max-width: 32rem;">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="{{ route('publico.loja', ['slug' => $slug]) }}">Vitrine</a></li>
                <li class="breadcrumb-item active" aria-current="page">Fidelidade</li>
            </ol>
        </nav>

        <h1 class="h4 fw-bold mb-1">{{ $programa?->nome_exibicao ?? 'Cartão fidelidade' }}</h1>
        <p class="text-muted small mb-4">{{ $empresa->nome }}</p>

        @if (! $programa || ! $programa->ativo)
            <div class="alert alert-secondary">
                Esta loja ainda não ativou o programa de fidelidade ou está em configuração.
            </div>
            <a href="{{ route('publico.loja', ['slug' => $slug]) }}" class="btn btn-outline-primary">Voltar à vitrine</a>
        @else
            <div class="vf-card p-4 mb-4">
                <p class="small text-muted mb-3">
                    A cada <strong>{{ $programa->pedidos_meta }}</strong> compras contabilizadas pela loja, você ganha:
                    <strong>{{ $programa->resumoRecompensa() }}</strong>.
                    @if ($programa->texto_recompensa)
                        <span class="d-block mt-2">{{ $programa->texto_recompensa }}</span>
                    @endif
                </p>

                <form action="{{ route('publico.fidelidade.consultar', ['slug' => $slug]) }}" method="post" class="mb-0">
                    @csrf
                    <label class="form-label small fw-semibold" for="tel-fid">Seu celular (mesmo usado nos pedidos)</label>
                    <input type="tel" name="telefone" id="tel-fid" value="{{ old('telefone', $telefone_digitado) }}"
                           class="form-control @error('telefone') is-invalid @enderror" placeholder="(11) 98888-7777" autocomplete="tel" required>
                    @error('telefone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <button type="submit" class="btn btn-primary w-100 mt-3">Ver meu cartão</button>
                </form>
            </div>

            @if ($telefone_digitado !== null)
                @if ($cartao)
                    @php
                        $meta = $programa->pedidos_meta;
                        $filled = min((int) $cartao->selos, $meta);
                        $cheio = $cartao->selos >= $meta;
                    @endphp
                    <div class="vf-card p-4 mb-3">
                        <h2 class="h6 fw-bold mb-3">Seu progresso</h2>
                        <div class="d-flex flex-wrap gap-2 justify-content-center mb-3">
                            @for ($i = 1; $i <= $meta; $i++)
                                <div class="rounded-circle d-flex align-items-center justify-content-center border {{ $i <= $filled ? 'bg-success text-white border-success' : 'bg-light text-muted border-secondary-subtle' }}"
                                     style="width: 2.25rem; height: 2.25rem; font-size: 0.75rem;">
                                    @if ($i <= $filled)
                                        <i class="bi bi-check-lg"></i>
                                    @else
                                        {{ $i }}
                                    @endif
                                </div>
                            @endfor
                        </div>
                        <p class="text-center small mb-0">
                            <strong>{{ $cartao->selos }}</strong> selo(s) · meta <strong>{{ $meta }}</strong>
                        </p>
                        @if ($cheio)
                            <div class="alert alert-success mt-3 mb-0 small">
                                <i class="bi bi-gift me-1"></i>Você completou a meta! Na próxima visita ou pedido, avise a loja para usar sua recompensa.
                            </div>
                        @endif
                    </div>
                @else
                    <div class="alert alert-info small mb-0">
                        Ainda não há selos neste telefone. Após sua primeira compra contabilizada pela loja, seus selos aparecerão aqui.
                    </div>
                @endif
            @endif

            <a href="{{ route('publico.loja', ['slug' => $slug]) }}" class="btn btn-link ps-0">← Continuar comprando</a>
        @endif
    </div>
@endsection
