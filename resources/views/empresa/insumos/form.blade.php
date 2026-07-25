@extends('layouts.empresa')

@section('title', $insumo->exists ? 'Editar insumo' : 'Novo insumo')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Insumos', 'url' => route('empresa.insumos.index')],
        ['label' => $insumo->exists ? 'Editar' : 'Novo', 'url' => '#'],
    ]])

    <h2 class="h4 fw-bold mb-3">{{ $insumo->exists ? 'Editar insumo' : 'Novo insumo' }}</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 small">
                @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="vf-card p-4" style="max-width: 44rem;">
        <form method="post" enctype="multipart/form-data"
              action="{{ $insumo->exists ? route('empresa.insumos.update', $insumo) : route('empresa.insumos.store') }}">
            @csrf
            @if ($insumo->exists)
                @method('PUT')
            @endif

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="nome">Nome do insumo</label>
                    <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome"
                           value="{{ old('nome', $insumo->nome) }}" maxlength="140" required placeholder="Ex.: Polpa de açaí">
                    @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="unidade_base">Como você mede</label>
                    <select class="form-select @error('unidade_base') is-invalid @enderror" id="unidade_base" name="unidade_base" required @disabled($insumo->exists)>
                        @foreach (\App\Enums\Estoque\UnidadeMedida::basesDisponiveis() as $u)
                            <option value="{{ $u->value }}" @selected(old('unidade_base', $insumo->unidade_base?->value ?? 'g') === $u->value)>
                                {{ $u === \App\Enums\Estoque\UnidadeMedida::Grama ? 'Peso (g / kg)' : ($u === \App\Enums\Estoque\UnidadeMedida::Mililitro ? 'Volume (ml / L)' : 'Unidades (un)') }}
                            </option>
                        @endforeach
                    </select>
                    @error('unidade_base')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @if ($insumo->exists)
                        <input type="hidden" name="unidade_base" value="{{ $insumo->unidade_base?->value }}">
                        <div class="form-text">A medida não muda depois do cadastro (o histórico ficaria inconsistente).</div>
                    @else
                        <div class="form-text">Você poderá informar em kg ou g — o sistema converte.</div>
                    @endif
                </div>

                @unless ($insumo->exists)
                    <div class="col-md-4">
                        <label class="form-label" for="saldo_inicial">Saldo inicial</label>
                        <input type="number" step="0.001" min="0" class="form-control @error('saldo_inicial') is-invalid @enderror"
                               id="saldo_inicial" name="saldo_inicial" value="{{ old('saldo_inicial', 0) }}">
                        @error('saldo_inicial')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Na unidade base escolhida (g, ml ou un).</div>
                    </div>
                @else
                    <div class="col-md-4">
                        <label class="form-label">Saldo atual</label>
                        <input type="text" class="form-control" value="{{ $insumo->saldoFormatado() }}" readonly disabled>
                        <div class="form-text"><a href="{{ route('empresa.insumos.movimentos', $insumo) }}">Repor / ajustar</a></div>
                    </div>
                @endunless

                <div class="col-md-4">
                    <label class="form-label" for="estoque_minimo">Estoque mínimo (alerta)</label>
                    <input type="number" step="0.001" min="0" class="form-control @error('estoque_minimo') is-invalid @enderror"
                           id="estoque_minimo" name="estoque_minimo" value="{{ old('estoque_minimo', $insumo->estoque_minimo ?? 0) }}">
                    @error('estoque_minimo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="custo_unitario">Custo por unidade base</label>
                    <input type="number" step="0.0001" min="0" class="form-control @error('custo_unitario') is-invalid @enderror"
                           id="custo_unitario" name="custo_unitario" value="{{ old('custo_unitario', $insumo->custo_unitario) }}" placeholder="Opcional">
                    @error('custo_unitario')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Ex.: R$ por g. Opcional.</div>
                </div>

                <div class="col-md-8">
                    <label class="form-label" for="foto">Foto do insumo</label>
                    <input type="file" class="form-control @error('foto') is-invalid @enderror" id="foto" name="foto" accept="image/*">
                    @error('foto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    @if ($insumo->urlFoto())
                        <img src="{{ $insumo->urlFoto() }}" alt="" width="64" height="64" class="rounded border object-fit-cover">
                    @endif
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input type="hidden" name="ativo" value="0">
                        <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo" @checked(old('ativo', $insumo->ativo ?? true))>
                        <label class="form-check-label" for="ativo">Insumo ativo</label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="{{ route('empresa.insumos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                @if ($insumo->exists)
                    <button type="submit" form="excluir-insumo" class="btn btn-outline-danger ms-auto">Excluir</button>
                @endif
            </div>
        </form>

        @if ($insumo->exists)
            <form id="excluir-insumo" method="post" action="{{ route('empresa.insumos.destroy', $insumo) }}"
                  onsubmit="return confirm('Excluir este insumo?');" class="d-none">
                @csrf
                @method('DELETE')
            </form>
        @endif
    </div>
@endsection
