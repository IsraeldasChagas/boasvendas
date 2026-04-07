@extends('layouts.empresa')

@section('title', 'Configurações')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('empresa.dashboard')],
        ['label' => 'Configurações', 'url' => route('empresa.configuracoes.index')],
    ]])

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h5 fw-bold mb-0">Configurações</h1>
    </div>

    <form action="{{ route('empresa.configuracoes.update') }}" method="post">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="vf-card p-4 mb-3">
                    <h2 class="h6 fw-bold mb-3">Dados da empresa</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="nome">Nome / razão social</label>
                            <input type="text" class="form-control form-control-sm @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome', $empresa->nome) }}" required maxlength="255">
                            @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="slug">Slug da loja (URL pública)</label>
                            <input type="text" class="form-control form-control-sm @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug', $empresa->slug) }}" maxlength="64" placeholder="ex.: minha-loja" autocomplete="off">
                            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <p class="small text-muted mb-0 mt-1">Apenas letras minúsculas, números e hífens. Deixe em branco se ainda não for usar a loja online.</p>
                            @if ($empresa->slug)
                                <p class="small mb-0 mt-2">
                                    <a href="{{ route('publico.loja', $empresa->slug) }}" target="_blank" rel="noopener">Abrir loja pública <i class="bi bi-box-arrow-up-right"></i></a>
                                </p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email_contato">E-mail de contato</label>
                            <input type="email" class="form-control form-control-sm @error('email_contato') is-invalid @enderror" id="email_contato" name="email_contato" value="{{ old('email_contato', $empresa->email_contato) }}" maxlength="255">
                            @error('email_contato')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cnpj">CNPJ</label>
                            <input type="text" class="form-control form-control-sm @error('cnpj') is-invalid @enderror" id="cnpj" name="cnpj" value="{{ old('cnpj', $empresa->cnpj) }}" maxlength="32">
                            @error('cnpj')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="vf-card p-4 mb-3">
                    <h2 class="h6 fw-bold mb-3">PIX na loja online</h2>
                    <p class="small text-muted mb-3">Para a opção <strong>PIX</strong> aparecer no checkout, preencha a <strong>chave PIX</strong> (abaixo) e/ou o <strong>Pix copia e cola</strong> (QR Code), e/ou um texto de instruções.</p>
                    <div class="mb-3">
                        <label class="form-label" for="loja_pix_instrucoes">Texto para o cliente <span class="text-muted fw-normal">(opcional)</span></label>
                        <textarea class="form-control form-control-sm @error('loja_pix_instrucoes') is-invalid @enderror" name="loja_pix_instrucoes" id="loja_pix_instrucoes" rows="4" maxlength="4000" placeholder="Ex.: Nome na chave, telefone para envio do comprovante…">{{ old('loja_pix_instrucoes', $empresa->loja_pix_instrucoes) }}</textarea>
                        @error('loja_pix_instrucoes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label" for="loja_pix_chave_tipo">Tipo da chave PIX</label>
                            <select class="form-select form-select-sm @error('loja_pix_chave_tipo') is-invalid @enderror" name="loja_pix_chave_tipo" id="loja_pix_chave_tipo">
                                <option value="">— Selecione —</option>
                                @foreach (\App\Models\Empresa::pixChaveTiposRotulos() as $val => $rot)
                                    <option value="{{ $val }}" @selected(old('loja_pix_chave_tipo', $empresa->loja_pix_chave_tipo) === $val)>{{ $rot }}</option>
                                @endforeach
                            </select>
                            @error('loja_pix_chave_tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="loja_pix_chave_valor">Chave PIX</label>
                            <input type="text" class="form-control form-control-sm @error('loja_pix_chave_valor') is-invalid @enderror" name="loja_pix_chave_valor" id="loja_pix_chave_valor" value="{{ old('loja_pix_chave_valor', $empresa->loja_pix_chave_valor) }}" maxlength="255" placeholder="Ex.: 11999999999 / seu@email.com / CPF / chave aleatória">
                            @error('loja_pix_chave_valor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <p class="small text-muted mb-0 mt-1">Essa é a <strong>chave PIX</strong> que o cliente vai ver no checkout.</p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="loja_pix_banco">Banco <span class="text-muted fw-normal">(opcional)</span></label>
                        <input type="text" class="form-control form-control-sm @error('loja_pix_banco') is-invalid @enderror" name="loja_pix_banco" id="loja_pix_banco" value="{{ old('loja_pix_banco', $empresa->loja_pix_banco) }}" maxlength="120" placeholder="Ex.: Nubank, Itaú, Banco do Brasil…">
                        @error('loja_pix_banco')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="loja_pix_copia_cola">Pix copia e cola <span class="text-muted fw-normal">(opcional)</span></label>
                        <textarea class="form-control form-control-sm font-monospace @error('loja_pix_copia_cola') is-invalid @enderror" name="loja_pix_copia_cola" id="loja_pix_copia_cola" rows="3" maxlength="8192" placeholder="Cole aqui o payload gerado no app do banco (gera o QR Code no checkout)">{{ old('loja_pix_copia_cola', $empresa->loja_pix_copia_cola) }}</textarea>
                        @error('loja_pix_copia_cola')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <p class="small text-muted mb-0 mt-1">Sem esse código não há QR automático; ainda pode usar só o texto acima.</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="vf-card p-3 mb-3">
                    <h2 class="h6 fw-bold mb-3">Contrato e plano</h2>
                    <dl class="small mb-0">
                        <dt class="text-muted fw-normal">Plano</dt>
                        <dd class="mb-2">{{ $empresa->plano?->nome ?? '—' }}</dd>
                        <dt class="text-muted fw-normal">Status</dt>
                        <dd class="mb-2">{{ \App\Models\Empresa::statusRotulos()[$empresa->status] ?? $empresa->status }}</dd>
                        <dt class="text-muted fw-normal">Cliente desde</dt>
                        <dd class="mb-0">{{ $empresa->cliente_desde?->format('d/m/Y') ?? '—' }}</dd>
                    </dl>
                    <p class="small text-muted mb-0 mt-3">Plano e status são alterados pelo administrador do sistema.</p>
                </div>

                <div class="vf-card p-3 mb-3">
                    <h2 class="h6 fw-bold mb-3">Módulos</h2>
                    @if ($empresa->modulos_resumo)
                        <ul class="list-unstyled small mb-0">
                            @foreach (preg_split('/\s*\+\s*|\s*,\s*/', $empresa->modulos_resumo, -1, PREG_SPLIT_NO_EMPTY) as $mod)
                                <li class="mb-2"><i class="bi bi-check-circle text-success me-1"></i>{{ trim($mod) }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="small text-muted mb-0">Nenhum resumo de módulos cadastrado.</p>
                    @endif
                    <p class="small text-muted mb-0 mt-3">Liberação de módulos é feita pelo suporte ou administrador.</p>
                </div>

                <button type="submit" class="btn btn-primary w-100">Salvar alterações</button>
            </div>
        </div>
    </form>
@endsection
