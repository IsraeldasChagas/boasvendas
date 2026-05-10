{{--
  Indicador do fluxo de compra na vitrine.
  @param string $slug
  @param string $passoAtual — loja | carrinho | checkout | pedido
  @param string|null $pedidoShowUrl — URL da página de confirmação (só na etapa pedido)
--}}
@php
    $p = $passoAtual ?? 'loja';
    $ordem = ['loja', 'carrinho', 'checkout', 'pedido'];
    $idx = array_search($p, $ordem, true);
    if ($idx === false) {
        $idx = 0;
    }
    $pedidoUrl = $pedidoShowUrl ?? null;
    $etapas = [
        ['id' => 'loja', 'label' => 'Cardápio', 'href' => route('publico.loja', ['slug' => $slug])],
        ['id' => 'carrinho', 'label' => 'Carrinho', 'href' => route('publico.carrinho', ['slug' => $slug])],
        ['id' => 'checkout', 'label' => 'Checkout', 'href' => route('publico.checkout', ['slug' => $slug])],
        ['id' => 'pedido', 'label' => 'Pedido', 'href' => $pedidoUrl],
    ];
@endphp
<nav class="vf-compra-fluxo mb-3" aria-label="Etapas da compra">
    <ol class="vf-compra-fluxo__list list-unstyled d-flex flex-wrap align-items-center gap-1 gap-sm-2 mb-0">
        @foreach ($etapas as $i => $e)
            @php
                $isDone = $i < $idx;
                $isCurrent = $i === $idx;
                $isTodo = $i > $idx;
                $href = $e['href'];
                if ($e['id'] === 'pedido' && ($href === null || $href === '')) {
                    $href = null;
                }
            @endphp
            <li class="vf-compra-fluxo__item d-flex align-items-center">
                @if ($i > 0)
                    <span class="vf-compra-fluxo__sep text-muted px-1" aria-hidden="true"><i class="bi bi-chevron-right small"></i></span>
                @endif
                @if ($isCurrent)
                    <span class="vf-compra-fluxo__atual badge rounded-pill bg-primary-subtle text-primary px-3 py-2" aria-current="step">{{ $e['label'] }}</span>
                @elseif ($href !== null)
                    <a href="{{ $href }}" class="vf-compra-fluxo__link text-decoration-none {{ $isDone ? 'text-success' : 'text-muted' }}">
                        @if ($isDone)
                            <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>
                        @endif
                        <span class="fw-semibold">{{ $e['label'] }}</span>
                    </a>
                @else
                    <span class="text-muted">{{ $e['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
