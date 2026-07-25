@extends('layouts.empresa')

@section('title', 'Ficha técnica — '.$produto->nome)

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Estoque', 'url' => route('empresa.estoque.index')],
        ['label' => $produto->nome, 'url' => route('empresa.estoque.produto', $produto)],
        ['label' => 'Ficha técnica', 'url' => '#'],
    ]])

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div class="d-flex align-items-center gap-3">
            @if ($produto->urlFoto())
                <img src="{{ $produto->urlFoto() }}" alt="" width="72" height="72" class="rounded border object-fit-cover">
            @else
                <span class="d-inline-flex align-items-center justify-content-center bg-light border rounded text-muted" style="width:72px;height:72px">
                    <i class="bi bi-egg-fried fs-3"></i>
                </span>
            @endif
            <div>
                <h2 class="h4 fw-bold mb-1">Ficha técnica — {{ $produto->nome }}</h2>
                <p class="small text-muted mb-0">
                    Receita do prato: ingredientes, quantidades e modo de preparo.
                    <a href="{{ route('empresa.produtos.edit', $produto) }}">Trocar foto do prato</a>
                </p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('empresa.insumos.index') }}" class="btn btn-outline-secondary btn-sm">Insumos</a>
            <a href="{{ route('empresa.estoque.produto', $produto) }}" class="btn btn-outline-secondary btn-sm">Estoque do produto</a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show">{{ session('warning') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if (! empty($erroFicha))
        <div class="alert alert-danger">
            <strong>Erro ao carregar a ficha técnica:</strong>
            <code class="small">{{ $erroFicha }}</code>
            <div class="small mt-2 mb-0">No servidor: <code>php artisan migrate --force</code> e <code>php artisan optimize:clear</code>.</div>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0 small">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    @if ($porcoesPossiveis !== null)
        <div class="alert {{ $porcoesPossiveis > 0 ? 'alert-info' : 'alert-danger' }} d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                @if ($porcoesPossiveis > 0)
                    Com os insumos em estoque, ainda dá para fazer
                    <strong>{{ $porcoesPossiveis }}</strong> {{ $porcoesPossiveis === 1 ? 'porção' : 'porções' }} de {{ $produto->nome }}.
                @else
                    <strong>Não dá para produzir agora:</strong> falta insumo na receita.
                @endif
            </div>
            @if ($insumoLimitante?->insumo)
                <span class="small">
                    Limitado por <strong>{{ $insumoLimitante->insumo->nome }}</strong>
                    (saldo {{ $insumoLimitante->insumo->saldoFormatado() }})
                </span>
            @endif
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="vf-card p-3 mb-3">
                <h3 class="h6 fw-bold mb-3"><i class="bi bi-list-check text-primary me-1"></i>Ingredientes da receita</h3>

                @if ($ficha->isNotEmpty())
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width:3rem"></th>
                                    <th>Ingrediente</th>
                                    <th style="width:12rem">Quantidade</th>
                                    <th class="text-end">Rende</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($ficha as $item)
                                    @php $possiveis = $item->porcoesPossiveis(); @endphp
                                    <tr>
                                        <td>
                                            @if ($item->insumo?->urlFoto())
                                                <img src="{{ $item->insumo->urlFoto() }}" alt="" width="36" height="36" class="rounded border object-fit-cover">
                                            @else
                                                <span class="d-inline-flex align-items-center justify-content-center bg-light border rounded text-muted" style="width:36px;height:36px">
                                                    <i class="bi bi-basket"></i>
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="small fw-medium">{{ $item->insumo?->nome ?? '—' }}</div>
                                            <div class="text-muted" style="font-size:.75rem">
                                                Saldo: {{ $item->insumo?->saldoFormatado() ?? '—' }}
                                                @if ($item->observacao) · {{ $item->observacao }} @endif
                                            </div>
                                        </td>
                                        <td>
                                            <form method="post" action="{{ route('empresa.estoque.ficha.update', [$produto, $item]) }}" class="d-flex gap-1">
                                                @csrf
                                                @method('PUT')
                                                <input type="number" step="0.001" min="0.001" name="quantidade" value="{{ $item->quantidade }}" class="form-control form-control-sm" style="max-width:5.5rem" required>
                                                <select name="unidade" class="form-select form-select-sm" style="max-width:5rem">
                                                    @foreach ($item->insumo?->unidadeBase()->compativeis() ?? [] as $u)
                                                        <option value="{{ $u->value }}" @selected($item->unidade?->value === $u->value)>{{ $u->sigla() }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Salvar quantidade"><i class="bi bi-check-lg"></i></button>
                                            </form>
                                        </td>
                                        <td class="text-end small {{ $possiveis !== null && $possiveis <= 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                            {{ $possiveis !== null ? $possiveis.'x' : '—' }}
                                        </td>
                                        <td class="text-end">
                                            <form method="post" action="{{ route('empresa.estoque.ficha.destroy', [$produto, $item]) }}" onsubmit="return confirm('Remover este ingrediente da receita?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="small text-muted">
                        Nenhum ingrediente ainda. Adicione o que a receita consome — ao vender o prato, o sistema baixa cada item.
                    </p>
                @endif

                @if ($insumosDisponiveis->isEmpty())
                    <div class="alert alert-warning small mb-0">
                        Cadastre os insumos primeiro (polpa, leite, copo…) em
                        <a href="{{ route('empresa.insumos.create') }}">Insumos → Novo insumo</a>.
                    </div>
                @else
                    <form method="post" action="{{ route('empresa.estoque.ficha.store', $produto) }}" class="row g-2 align-items-end border-top pt-3">
                        @csrf
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold" for="insumo_id">Ingrediente</label>
                            <select class="form-select form-select-sm" id="insumo_id" name="insumo_id" required data-vf-insumo-select>
                                <option value="">— Escolha —</option>
                                @foreach ($insumosDisponiveis as $i)
                                    <option value="{{ $i->id }}" data-unidades="{{ collect($i->unidadeBase()->compativeis())->map(fn ($u) => $u->value)->implode(',') }}">
                                        {{ $i->nome }} ({{ $i->saldoFormatado() }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold" for="quantidade">Quantidade</label>
                            <input type="number" step="0.001" min="0.001" class="form-control form-control-sm" id="quantidade" name="quantidade" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold" for="unidade">Unidade</label>
                            <select class="form-select form-select-sm" id="unidade" name="unidade" required data-vf-unidade-select>
                                @foreach (\App\Enums\Estoque\UnidadeMedida::cases() as $u)
                                    <option value="{{ $u->value }}">{{ $u->sigla() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100">Adicionar</button>
                        </div>
                        <div class="col-12">
                            <input type="text" class="form-control form-control-sm" name="observacao" maxlength="200" placeholder="Observação da receita (opcional, ex.: bater com o leite)">
                        </div>
                    </form>
                @endif
            </div>
        </div>

        <div class="col-lg-5">
            <div class="vf-card p-3">
                <h3 class="h6 fw-bold mb-3"><i class="bi bi-journal-text text-primary me-1"></i>Preparo e rendimento</h3>
                <form method="post" action="{{ route('empresa.estoque.ficha.cabecalho', $produto) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold" for="ficha_rendimento">Rende (porções)</label>
                            <input type="number" min="1" max="10000" class="form-control form-control-sm" id="ficha_rendimento"
                                   name="ficha_rendimento" value="{{ old('ficha_rendimento', $produto->fichaRendimento()) }}" required>
                            <div class="form-text">Quantas porções saem das quantidades acima.</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold" for="ficha_tempo_preparo_min">Tempo (min)</label>
                            <input type="number" min="1" max="1440" class="form-control form-control-sm" id="ficha_tempo_preparo_min"
                                   name="ficha_tempo_preparo_min" value="{{ old('ficha_tempo_preparo_min', $produto->ficha_tempo_preparo_min) }}" placeholder="Opcional">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="modo_preparo">Modo de preparo (para a cozinha)</label>
                        <textarea class="form-control form-control-sm" id="modo_preparo" name="modo_preparo" rows="10"
                                  maxlength="10000" placeholder="1. Bata a polpa com o leite&#10;2. Monte o copo&#10;3. Finalize com granola">{{ old('modo_preparo', $produto->modo_preparo) }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Salvar ficha</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const insumoSelect = document.querySelector('[data-vf-insumo-select]');
        const unidadeSelect = document.querySelector('[data-vf-unidade-select]');
        if (!insumoSelect || !unidadeSelect) return;

        const todas = Array.from(unidadeSelect.options).map(o => ({ value: o.value, text: o.text }));

        // Só oferece unidades compatíveis com a medida do insumo (peso, volume ou contagem).
        function filtrarUnidades() {
            const opt = insumoSelect.selectedOptions[0];
            const permitidas = (opt?.dataset.unidades || '').split(',').filter(Boolean);
            unidadeSelect.innerHTML = '';
            todas.filter(u => permitidas.length === 0 || permitidas.includes(u.value))
                .forEach(u => unidadeSelect.add(new Option(u.text, u.value)));
        }

        insumoSelect.addEventListener('change', filtrarUnidades);
        filtrarUnidades();
    });
    </script>
@endsection
