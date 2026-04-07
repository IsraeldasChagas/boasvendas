@php
    $lista = is_array($opcoesLinha ?? null) ? ($opcoesLinha['adicionais'] ?? []) : [];
@endphp
@if ($lista !== [])
    <ul class="list-unstyled small text-muted mb-0 mt-1 ps-2 border-start">
        @foreach ($lista as $op)
            <li>
                @if (($op['tipo'] ?? '') === \App\Models\Adicional::TIPO_RETIRAR || ($op['tipo'] ?? '') === 'retirar_ingrediente')
                    <i class="bi bi-dash-circle me-1"></i>Sem {{ $op['nome'] ?? '' }}
                @else
                    <i class="bi bi-plus-circle me-1"></i>{{ $op['nome'] ?? '' }}
                    @if ((float) ($op['preco'] ?? 0) > 0)
                        <span class="text-success">(+ R$ {{ number_format((float) $op['preco'], 2, ',', '.') }})</span>
                    @endif
                @endif
            </li>
        @endforeach
    </ul>
@endif
