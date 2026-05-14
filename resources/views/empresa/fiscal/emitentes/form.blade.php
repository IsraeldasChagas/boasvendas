@extends('layouts.empresa')

@section('title', $emitente->exists ? 'Fiscal — Editar emitente' : 'Fiscal — Novo emitente')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Fiscal', 'url' => route('empresa.fiscal.dashboard')],
        ['label' => 'Emitentes', 'url' => route('empresa.fiscal.emitentes.index')],
        ['label' => $emitente->exists ? 'Editar' : 'Novo', 'url' => '#'],
    ]])

    <h2 class="h4 fw-bold mb-3">{{ $emitente->exists ? 'Editar emitente' : 'Novo emitente' }}</h2>

    <div class="vf-card p-4" style="max-width: 42rem;">
        <form method="post" action="{{ $emitente->exists ? route('empresa.fiscal.emitentes.update', $emitente) : route('empresa.fiscal.emitentes.store') }}">
            @csrf
            @if ($emitente->exists)
                @method('PUT')
            @endif
            <div class="row g-2">
                <div class="col-md-8 mb-2">
                    <label class="form-label small fw-semibold" for="rz">Razão social</label>
                    <input type="text" class="form-control form-control-sm @error('razao_social') is-invalid @enderror" id="rz" name="razao_social" value="{{ old('razao_social', $emitente->razao_social) }}" required maxlength="180">
                    @error('razao_social')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-2">
                    <label class="form-label small fw-semibold" for="cnpj">CNPJ</label>
                    <input type="text" class="form-control form-control-sm @error('cnpj') is-invalid @enderror" id="cnpj" name="cnpj" value="{{ old('cnpj', $emitente->cnpj) }}" required maxlength="18">
                    @error('cnpj')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label small fw-semibold" for="fant">Nome fantasia</label>
                    <input type="text" class="form-control form-control-sm" id="fant" name="nome_fantasia" value="{{ old('nome_fantasia', $emitente->nome_fantasia) }}" maxlength="180">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label small fw-semibold" for="ie">Inscrição estadual</label>
                    <input type="text" class="form-control form-control-sm" id="ie" name="inscricao_estadual" value="{{ old('inscricao_estadual', $emitente->inscricao_estadual) }}" maxlength="32">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label small fw-semibold" for="reg">Regime tributário</label>
                    <input type="text" class="form-control form-control-sm" id="reg" name="regime_tributario" value="{{ old('regime_tributario', $emitente->regime_tributario) }}" maxlength="32" placeholder="Simples, Normal, MEI…">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label small fw-semibold" for="amb">Ambiente</label>
                    <select class="form-select form-select-sm" id="amb" name="ambiente" required>
                        @foreach (\App\Enums\Fiscal\FiscalAmbiente::cases() as $a)
                            <option value="{{ $a->value }}" @selected(old('ambiente', $emitente->ambiente?->value ?? 'homologacao') === $a->value)>{{ $a->rotulo() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label small fw-semibold" for="csc">CSC</label>
                    <input type="text" class="form-control form-control-sm" id="csc" name="csc" value="{{ old('csc', $emitente->csc) }}" maxlength="120" autocomplete="off">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label small fw-semibold" for="cscid">CSC ID</label>
                    <input type="text" class="form-control form-control-sm" id="cscid" name="csc_id" value="{{ old('csc_id', $emitente->csc_id) }}" maxlength="32" autocomplete="off">
                </div>
                <div class="col-12 mb-2">
                    <label class="form-label small fw-semibold" for="certpath">Caminho do certificado (A1) no servidor</label>
                    <input type="text" class="form-control form-control-sm font-monospace" id="certpath" name="certificado_path" value="{{ old('certificado_path', $emitente->certificado_path) }}" maxlength="512" placeholder="fiscal-certificados/empresa_1/cert.pfx">
                    <p class="small text-muted mb-0 mt-1">Armazene fora do webroot; em produção use disco privado e permissões restritas.</p>
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label small fw-semibold" for="certsenha">Senha do certificado</label>
                    <input type="password" class="form-control form-control-sm" id="certsenha" name="certificado_senha" value="" maxlength="500" autocomplete="new-password" placeholder="{{ $emitente->exists ? '•••• (deixe em branco para manter)' : '' }}">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label small fw-semibold" for="emissor">Emissor / driver</label>
                    <select class="form-select form-select-sm" id="emissor" name="emissor_tipo" required>
                        @foreach (\App\Enums\Fiscal\FiscalEmissorDriver::cases() as $d)
                            <option value="{{ $d->value }}" @selected(old('emissor_tipo', $emitente->emissor_tipo) === $d->value)>{{ $d->rotulo() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-8 mb-2">
                    <label class="form-label small fw-semibold" for="apiurl">URL da API (quando aplicável)</label>
                    <input type="url" class="form-control form-control-sm" id="apiurl" name="api_url" value="{{ old('api_url', $emitente->api_url) }}" maxlength="512" placeholder="https://…">
                </div>
                <div class="col-md-12 mb-2">
                    <label class="form-label small fw-semibold" for="apitok">Token / API key</label>
                    <input type="password" class="form-control form-control-sm font-monospace" id="apitok" name="api_token" value="" maxlength="2000" autocomplete="off" placeholder="{{ $emitente->exists ? 'Deixe em branco para manter' : '' }}">
                </div>
                <div class="col-12 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo-em" @checked(old('ativo', $emitente->ativo ?? true))>
                        <label class="form-check-label" for="ativo-em">Emitente ativo</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Salvar</button>
            <a href="{{ route('empresa.fiscal.emitentes.index') }}" class="btn btn-link">Cancelar</a>
        </form>
    </div>
@endsection
