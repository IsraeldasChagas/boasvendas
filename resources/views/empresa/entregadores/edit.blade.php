@extends('layouts.empresa')

@section('title', 'Editar entregador')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Meus entregadores', 'url' => route('empresa.entregadores.index')],
        ['label' => $entregador->nome, 'url' => route('empresa.entregadores.edit', $entregador)],
    ]])

    <div class="vf-card p-4" style="max-width: 36rem;">
        <h2 class="h5 fw-bold mb-3">Editar entregador</h2>
        <form action="{{ route('empresa.entregadores.update', $entregador) }}" method="post" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label" for="nome">Nome <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome', $entregador->nome) }}" required maxlength="255">
                @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="whatsapp">WhatsApp <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('whatsapp') is-invalid @enderror" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $entregador->whatsapp) }}" required maxlength="32" placeholder="(11) 98888-7777">
                @error('whatsapp')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            @if ($entregador->urlFoto())
                <div class="mb-2">
                    <span class="form-label d-block">Foto atual</span>
                    <img src="{{ $entregador->urlFoto() }}" alt="" width="96" height="96" class="rounded border object-fit-cover" style="object-fit:cover">
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remover_foto" id="remover_foto" value="1" @checked(old('remover_foto'))>
                    <label class="form-check-label" for="remover_foto">Remover foto atual</label>
                </div>
            @endif
            <div class="mb-3">
                <label class="form-label" for="foto">{{ $entregador->urlFoto() ? 'Substituir foto' : 'Foto do entregador' }}</label>
                <input type="file" class="form-control @error('foto') is-invalid @enderror" id="foto" name="foto" accept="image/jpeg,image/png,image/webp">
                @error('foto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <p class="small text-muted mb-0 mt-1">JPG, PNG ou WebP · até 2&nbsp;MB.</p>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="moto_modelo">Moto — modelo</label>
                    <input type="text" class="form-control @error('moto_modelo') is-invalid @enderror" id="moto_modelo" name="moto_modelo" value="{{ old('moto_modelo', $entregador->moto_modelo) }}" maxlength="120">
                    @error('moto_modelo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="moto_cor">Moto — cor</label>
                    <input type="text" class="form-control @error('moto_cor') is-invalid @enderror" id="moto_cor" name="moto_cor" value="{{ old('moto_cor', $entregador->moto_cor) }}" maxlength="64">
                    @error('moto_cor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="moto_placa">Moto — placa</label>
                    <input type="text" class="form-control @error('moto_placa') is-invalid @enderror" id="moto_placa" name="moto_placa" value="{{ old('moto_placa', $entregador->moto_placa) }}" maxlength="16">
                    @error('moto_placa')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="ordem">Ordem de prioridade</label>
                    <input type="number" class="form-control @error('ordem') is-invalid @enderror" id="ordem" name="ordem" value="{{ old('ordem', $entregador->ordem) }}" min="0" max="99999">
                    @error('ordem')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="form-check mb-4">
                <input type="hidden" name="ativo" value="0">
                <input class="form-check-input" type="checkbox" name="ativo" id="ativo" value="1" @checked(old('ativo', $entregador->ativo ? '1' : '0') === '1')>
                <label class="form-check-label" for="ativo">Ativo (aparece na tela do pedido)</label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="{{ route('empresa.entregadores.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
