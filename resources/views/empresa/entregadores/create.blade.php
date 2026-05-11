@extends('layouts.empresa')

@section('title', 'Novo entregador')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Meus entregadores', 'url' => route('empresa.entregadores.index')],
        ['label' => 'Novo', 'url' => route('empresa.entregadores.create')],
    ]])

    <div class="vf-card mb-3 border border-primary border-2 shadow-sm overflow-hidden rounded-2" style="max-width: 36rem;">
        <div class="px-4 py-3 bg-primary-subtle bg-opacity-25 border-bottom border-primary border-opacity-25">
            <h2 class="h5 fw-bold mb-0"><i class="bi bi-person-plus text-primary me-1"></i>Novo entregador</h2>
        </div>
        <div class="p-4 pt-3">
        <form action="{{ route('empresa.entregadores.store') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="nome">Nome <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome') }}" required maxlength="255">
                @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="whatsapp">WhatsApp <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('whatsapp') is-invalid @enderror" id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}" required maxlength="32" placeholder="(11) 98888-7777">
                @error('whatsapp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <p class="small text-muted mb-0 mt-1">DDD + número. Usado no botão de chamar na tela do pedido.</p>
            </div>
            <div class="mb-3">
                <label class="form-label" for="foto">Foto do entregador <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('foto') is-invalid @enderror" id="foto" name="foto" accept="image/jpeg,image/png,image/webp" required>
                @error('foto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <p class="small text-muted mb-0 mt-1">JPG, PNG ou WebP · até 2&nbsp;MB.</p>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="moto_modelo">Moto — modelo</label>
                    <input type="text" class="form-control @error('moto_modelo') is-invalid @enderror" id="moto_modelo" name="moto_modelo" value="{{ old('moto_modelo') }}" maxlength="120" placeholder="Ex.: Honda CG 160">
                    @error('moto_modelo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="moto_cor">Moto — cor</label>
                    <input type="text" class="form-control @error('moto_cor') is-invalid @enderror" id="moto_cor" name="moto_cor" value="{{ old('moto_cor') }}" maxlength="64" placeholder="Ex.: Vermelha">
                    @error('moto_cor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="moto_placa">Moto — placa</label>
                    <input type="text" class="form-control @error('moto_placa') is-invalid @enderror" id="moto_placa" name="moto_placa" value="{{ old('moto_placa') }}" maxlength="16" placeholder="ABC1D23">
                    @error('moto_placa')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="ordem">Ordem de prioridade</label>
                    <input type="number" class="form-control @error('ordem') is-invalid @enderror" id="ordem" name="ordem" value="{{ old('ordem', 0) }}" min="0" max="99999">
                    @error('ordem')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <p class="small text-muted mb-0 mt-1">0 = primeiro da lista; números maiores vêm depois.</p>
                </div>
            </div>
            <div class="form-check mb-4">
                <input type="hidden" name="ativo" value="0">
                <input class="form-check-input" type="checkbox" name="ativo" id="ativo" value="1" @checked(old('ativo', true))>
                <label class="form-check-label" for="ativo">Ativo (aparece na tela do pedido)</label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="{{ route('empresa.entregadores.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
        </div>
    </div>
@endsection
