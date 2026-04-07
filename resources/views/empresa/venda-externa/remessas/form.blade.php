@extends('layouts.empresa')

@section('title', $remessa->exists ? 'Editar entrega' : 'Nova entrega')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Entregas', 'url' => route('empresa.venda-externa.remessas.index')],
        ['label' => $remessa->exists ? 'Editar #'.$remessa->id : 'Nova', 'url' => '#'],
    ]])

    <div class="vf-card p-4" style="max-width: 36rem;">
        <h2 class="h5 fw-bold mb-4">{{ $remessa->exists ? 'Editar entrega #'.$remessa->id : 'Nova entrega' }}</h2>
        <form action="{{ $remessa->exists ? route('empresa.venda-externa.remessas.update', $remessa) : route('empresa.venda-externa.remessas.store') }}" method="post">
            @csrf
            @if ($remessa->exists)
                @method('PUT')
            @endif
            @php
                $defaultAcerto = 'nao_acertado';
                if ($remessa->exists) {
                    $defaultAcerto = $remessa->estaAcertada() ? 'acertado' : 'nao_acertado';
                }
            @endphp
            <div class="mb-3">
                <label class="form-label" for="produto_id">Produto</label>
                <select class="form-select @error('produto_id') is-invalid @enderror" id="produto_id" name="produto_id">
                    <option value="">— Selecione —</option>
                    @foreach ($produtos as $p)
                        <option value="{{ $p->id }}" @selected((string) old('produto_id', $remessa->produto_id) === (string) $p->id)>{{ $p->nome }}</option>
                    @endforeach
                </select>
                @error('produto_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Bem simples: selecione o produto que você está entregando para o parceiro revender.</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="ve_ponto_id">Parceiro (opcional)</label>
                <select class="form-select @error('ve_ponto_id') is-invalid @enderror" id="ve_ponto_id" name="ve_ponto_id">
                    <option value="">— Nenhum —</option>
                    @foreach ($pontos as $pt)
                        <option value="{{ $pt->id }}" @selected((string) old('ve_ponto_id', $remessa->ve_ponto_id) === (string) $pt->id)>{{ $pt->nome }}@if ($pt->regiao) · {{ $pt->regiao }}@endif</option>
                    @endforeach
                </select>
                @error('ve_ponto_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Para marcar como <strong>Acertado</strong>, o parceiro é obrigatório.</div>
            </div>
            <div class="mb-4">
                <label class="form-label" for="entrega_acerto">Status</label>
                <select class="form-select @error('entrega_acerto') is-invalid @enderror" id="entrega_acerto" name="entrega_acerto" required>
                    <option value="nao_acertado" @selected(old('entrega_acerto', $defaultAcerto) === 'nao_acertado')>Não acertado</option>
                    <option value="acertado" @selected(old('entrega_acerto', $defaultAcerto) === 'acertado')>Acertado</option>
                </select>
                @error('entrega_acerto')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">Salvar</button>
                @if ($remessa->exists)
                    <a href="{{ route('empresa.venda-externa.remessas.show', $remessa) }}" class="btn btn-outline-secondary">Cancelar</a>
                @else
                    <a href="{{ route('empresa.venda-externa.remessas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                @endif
            </div>
        </form>
    </div>
@endsection
