@extends('layouts.publico')

@section('title', $empresa->nome)

@section('content')
    <div class="container">
        <div class="mb-3 d-flex flex-wrap align-items-center gap-2">
            <span class="vf-badge bg-secondary-subtle text-secondary">Cardápio online</span>
            @if ($empresa->fidelidadePrograma && $empresa->fidelidadePrograma->ativo)
                <a href="{{ route('publico.fidelidade', ['slug' => $slug]) }}" class="vf-badge bg-primary-subtle text-primary text-decoration-none">
                    <i class="bi bi-award me-1"></i>Cartão fidelidade
                </a>
            @endif
        </div>

        <form action="{{ route('publico.loja', ['slug' => $slug]) }}" method="get" class="vf-filter-bar mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-6 col-lg-4">
                    <label class="form-label small text-muted mb-1" for="loja-cat">Categoria</label>
                    <select class="form-select form-select-sm" id="loja-cat" name="categoria_id" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        @foreach ($categorias as $cat)
                            <option value="{{ $cat->id }}" @selected((string) request('categoria_id') === (string) $cat->id)>{{ $cat->nome }}</option>
                        @endforeach
                    </select>
                </div>
                @if (request()->filled('categoria_id'))
                    <div class="col-auto">
                        <a href="{{ route('publico.loja', ['slug' => $slug]) }}" class="btn btn-sm btn-outline-secondary">Limpar</a>
                    </div>
                @endif
            </div>
        </form>

        <div class="row g-3">
            @forelse ($produtos as $pr)
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="{{ route('publico.produto', ['slug' => $slug, 'produto_id' => $pr->id]) }}" class="text-decoration-none text-dark">
                        <div class="vf-card vf-product-card h-100 overflow-hidden">
                            <div class="ratio ratio-4x3 bg-primary-subtle overflow-hidden">
                                @if ($pr->foto)
                                    <img src="{{ $pr->urlFoto() }}" alt="" class="w-100 h-100 object-fit-cover">
                                @else
                                    <div class="d-flex align-items-center justify-content-center w-100 h-100">
                                        <i class="bi bi-image text-primary opacity-50 fs-1"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="p-3">
                                <div class="fw-semibold">{{ $pr->nome }}</div>
                                @if ($pr->categoria)
                                    <div class="small text-muted">{{ $pr->categoria->nome }}</div>
                                @endif
                                @if ($pr->permite_adicionais && ($pr->adicionais_loja_count ?? 0) > 0)
                                    <div class="small mt-1"><span class="vf-badge bg-info-subtle text-info">Personalizável</span></div>
                                @endif
                                <div class="text-success fw-bold mt-1">R$ {{ number_format((float) $pr->preco, 2, ',', '.') }}</div>
                            </div>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-12">
                    <div class="vf-card p-4 text-center text-muted">
                        Nenhum produto disponível na vitrine no momento.
                    </div>
                </div>
            @endforelse
        </div>

        @if ($produtos->hasPages())
            <div class="mt-4 d-flex justify-content-center">{{ $produtos->links() }}</div>
        @endif
    </div>
@endsection
