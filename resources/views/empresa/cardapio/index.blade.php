@extends('layouts.empresa')

@section('title', 'Cardápio (consulta)')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('empresa.dashboard')],
        ['label' => 'Cardápio', 'url' => route('empresa.cardapio.index')],
    ]])

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="h5 fw-bold mb-0">Cardápio do salão</h2>
            <p class="small text-muted mb-0">Mesmos produtos ativos do cadastro — só leitura, para consulta no atendimento.</p>
        </div>
    </div>

    @if ($categorias->isNotEmpty())
        <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="{{ route('empresa.cardapio.index') }}"
               class="btn btn-sm {{ $categoriaFiltro === null ? 'btn-primary' : 'btn-outline-secondary' }}">Todas</a>
            @foreach ($categorias as $cat)
                <a href="{{ route('empresa.cardapio.index', ['categoria' => $cat->id]) }}"
                   class="btn btn-sm {{ (int) $categoriaFiltro === (int) $cat->id ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $cat->nome }}</a>
            @endforeach
        </div>
    @endif

    @if ($produtos->isEmpty())
        <div class="alert alert-light border text-muted mb-0">
            Nenhum produto ativo neste filtro. Cadastre ou ative produtos em <a href="{{ route('empresa.produtos.index') }}">Produtos</a>.
        </div>
    @else
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
            @foreach ($produtos as $pr)
                <div class="col">
                    <div class="card h-100 shadow-sm border-0">
                        @php $foto = $pr->urlFoto(); @endphp
                        <div class="ratio ratio-1x1 bg-light rounded-top overflow-hidden">
                            @if ($foto)
                                <img src="{{ $foto }}" alt="" class="object-fit-cover w-100 h-100">
                            @else
                                <div class="d-flex align-items-center justify-content-center text-muted">
                                    <i class="bi bi-image fs-1"></i>
                                </div>
                            @endif
                        </div>
                        <div class="card-body d-flex flex-column p-2 p-md-3">
                            <div class="small text-muted text-truncate">{{ $pr->categoria?->nome ?? '—' }}</div>
                            <div class="fw-semibold small">{{ $pr->nome }}</div>
                            @if ($pr->sku)
                                <div class="small text-muted">SKU {{ $pr->sku }}</div>
                            @endif
                            <div class="mt-auto pt-2 text-success fw-bold">R$ {{ number_format((float) $pr->preco, 2, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
