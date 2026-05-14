@extends('layouts.empresa')

@section('title', 'Histórico de pontos')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Fidelidade', 'url' => route('empresa.fidelidade.programa')],
        ['label' => 'Cartão Fidelidade', 'url' => route('empresa.fidelidade.cartoes')],
        ['label' => 'Histórico', 'url' => route('empresa.fidelidade.cartoes.historico', $fidelidadeCartao)],
    ]])

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 fw-bold mb-0">Histórico de pontos</h2>
        <a href="{{ route('empresa.fidelidade.cartoes', ['q' => $fidelidadeCartao->telefone_normalizado]) }}" class="btn btn-outline-secondary btn-sm">Voltar aos cartões</a>
    </div>

    <div class="vf-card p-3 mb-3">
        <p class="small mb-0">
            Cartão <strong>{{ $fidelidadeCartao->codigo_fidelidade ?: '—' }}</strong>
            · Telefone <span class="font-monospace">{{ $fidelidadeCartao->telefoneMascarado() }}</span>
            · Pontos atuais <strong>{{ (int) ($fidelidadeCartao->pontos ?? 0) }}</strong>
        </p>
    </div>

    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 vf-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th class="text-end">Δ pontos</th>
                        <th>Descrição</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($historicos as $h)
                        <tr>
                            <td class="small text-nowrap">{{ $h->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="small"><code>{{ $h->tipo_movimento }}</code></td>
                            <td class="text-end small fw-semibold @if ($h->pontos > 0) text-success @elseif ($h->pontos < 0) text-danger @endif">{{ $h->pontos > 0 ? '+' : '' }}{{ $h->pontos }}</td>
                            <td class="small">{{ $h->descricao ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Nenhum registro ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="vf-card p-3 mt-3 border border-danger-subtle bg-danger-subtle bg-opacity-10">
        <p class="small fw-semibold text-danger mb-2">Zona de risco</p>
        <p class="small text-muted mb-3">Excluir remove o cartão, selos, pontos e todo o histórico. O telefone fica livre para um novo cadastro.</p>
        <form action="{{ route('empresa.fidelidade.cartoes.destroy', $fidelidadeCartao) }}" method="post" onsubmit="return confirm('Excluir este cartão para sempre? Esta ação não pode ser desfeita.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger">Excluir cartão</button>
        </form>
    </div>
@endsection
