@extends('layouts.empresa')

@section('title', 'Fiscal — Configurações')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Fiscal', 'url' => route('empresa.fiscal.dashboard')],
        ['label' => 'Configurações', 'url' => route('empresa.fiscal.configuracoes.edit')],
    ]])

    <h2 class="h4 fw-bold mb-3">Configurações fiscais</h2>
    <p class="text-muted small mb-4">Parâmetros globais da loja. Integrações reais (Focus, NFE.io, etc.) serão ligadas nos drivers sem alterar esta tela.</p>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="vf-card p-4" style="max-width: 40rem;">
        <form action="{{ route('empresa.fiscal.configuracoes.update') }}" method="post">
            @csrf
            @method('PUT')
            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" name="modulo_ativo" value="1" id="mod-fiscal-ativo" @checked(old('modulo_ativo', $config->modulo_ativo))>
                <label class="form-check-label fw-semibold" for="mod-fiscal-ativo">Habilitar módulo fiscal</label>
            </div>

            <fieldset class="mb-4">
                <legend class="form-label fw-semibold small text-muted text-uppercase">Modo de emissão</legend>
                @foreach (\App\Enums\Fiscal\FiscalModoEmissao::cases() as $m)
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="modo_emissao" id="modo-{{ $m->value }}" value="{{ $m->value }}" @checked(old('modo_emissao', $config->modo_emissao->value) === $m->value)>
                        <label class="form-check-label" for="modo-{{ $m->value }}">{{ $m->rotulo() }}</label>
                    </div>
                @endforeach
                @error('modo_emissao')<div class="text-danger small">{{ $message }}</div>@enderror
            </fieldset>

            <fieldset class="mb-4">
                <legend class="form-label fw-semibold small text-muted text-uppercase">Tipo de documento</legend>
                @foreach (\App\Enums\Fiscal\FiscalTipoDocumento::cases() as $t)
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="tipo_documento" id="tipo-{{ $t->value }}" value="{{ $t->value }}" @checked(old('tipo_documento', $config->tipo_documento->value) === $t->value)>
                        <label class="form-check-label" for="tipo-{{ $t->value }}">{{ $t->rotulo() }}</label>
                    </div>
                @endforeach
                @error('tipo_documento')<div class="text-danger small">{{ $message }}</div>@enderror
            </fieldset>

            <fieldset class="mb-4">
                <legend class="form-label fw-semibold small text-muted text-uppercase">Ambiente</legend>
                @foreach (\App\Enums\Fiscal\FiscalAmbiente::cases() as $a)
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="ambiente" id="amb-{{ $a->value }}" value="{{ $a->value }}" @checked(old('ambiente', $config->ambiente->value) === $a->value)>
                        <label class="form-check-label" for="amb-{{ $a->value }}">{{ $a->rotulo() }}</label>
                    </div>
                @endforeach
                @error('ambiente')<div class="text-danger small">{{ $message }}</div>@enderror
            </fieldset>

            <div class="mb-4">
                <label class="form-label fw-semibold" for="driver-padrao">Driver padrão (fallback)</label>
                <select class="form-select" name="emissor_driver_padrao" id="driver-padrao">
                    <option value="">— Herdar do emitente —</option>
                    @foreach (\App\Enums\Fiscal\FiscalEmissorDriver::cases() as $d)
                        <option value="{{ $d->value }}" @selected(old('emissor_driver_padrao', $config->emissor_driver_padrao?->value) === $d->value)>{{ $d->rotulo() }}</option>
                    @endforeach
                </select>
                <p class="small text-muted mt-1 mb-0">Usado quando o emitente não define driver; preparação para troca futura entre APIs.</p>
                @error('emissor_driver_padrao')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="btn btn-primary">Salvar</button>
            <a href="{{ route('empresa.fiscal.dashboard') }}" class="btn btn-link">Dashboard</a>
        </form>
    </div>
@endsection
