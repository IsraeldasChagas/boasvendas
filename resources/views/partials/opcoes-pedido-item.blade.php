@php
    $opArr = is_array($opcoesLinha ?? null) ? $opcoesLinha : [];
    $lista = is_array($opArr['adicionais'] ?? null) ? $opArr['adicionais'] : [];
    $obsItem = trim((string) ($opArr['observacao'] ?? ''));
    $notaItem = (int) ($opArr['nota_produto'] ?? 0);
@endphp
@if ($lista !== [] || $obsItem !== '' || ($notaItem >= 1 && $notaItem <= 5))
    <div class="small text-muted mt-1 ps-2 border-start">
        @if ($notaItem >= 1 && $notaItem <= 5)
            <div class="text-warning lh-1 mb-1" role="img" aria-label="Nota {{ $notaItem }} de 5">
                @for ($i = 1; $i <= 5; $i++)
                    <i class="bi {{ $i <= $notaItem ? 'bi-star-fill' : 'bi-star' }}" aria-hidden="true"></i>
                @endfor
            </div>
        @endif
        @if ($obsItem !== '')
            <div class="mb-1"><span class="text-muted">Obs.:</span> {{ $obsItem }}</div>
        @endif
        @if ($lista !== [])
            <ul class="list-unstyled mb-0">
                @foreach ($lista as $op)
                    <li>
                        @if (($op['tipo'] ?? '') === \App\Models\Adicional::TIPO_RETIRAR || ($op['tipo'] ?? '') === 'retirar_ingrediente')
                            @php $qRet = (int) ($op['quantidade'] ?? 1); @endphp
                            <i class="bi bi-dash-circle me-1"></i>{{ $op['nome'] ?? '' }}@if ($qRet > 1)<span class="text-muted"> ×{{ $qRet }}</span>@endif
                        @else
                            @php $qOp = (int) ($op['quantidade'] ?? 1); @endphp
                            <i class="bi bi-plus-circle me-1"></i>{{ $op['nome'] ?? '' }}@if ($qOp > 1)<span class="text-muted"> ×{{ $qOp }}</span>@endif
                            @if ((float) ($op['preco'] ?? 0) > 0)
                                <span class="text-success">(+ R$ {{ number_format((float) $op['preco'] * max(1, $qOp), 2, ',', '.') }})</span>
                            @endif
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
