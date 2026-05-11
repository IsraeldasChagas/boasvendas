@extends('layouts.empresa')

@section('title', 'Configurações')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('empresa.dashboard')],
        ['label' => 'Configurações', 'url' => route('empresa.configuracoes.index')],
    ]])

    @push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <style>
        .vf-config-section-toggle:focus-visible { outline: 2px solid var(--bs-primary); outline-offset: 2px; }
        .vf-config-section-toggle[aria-expanded="true"] .vf-config-chevron { transform: rotate(180deg); }
        .vf-config-chevron { transition: transform 0.2s ease; display: inline-block; }
    </style>
    @endpush

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Não foi possível salvar.</strong> Corrija abaixo ou role a página para ver os campos em vermelho.
            <ul class="mb-0 mt-2 small">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif
    @if (! \Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_confirmar_pedidos'))
        <div class="alert alert-warning small mb-3" role="alert">
            <strong>Som e confirmação de pedidos:</strong> no servidor ainda não existe a coluna no banco. Rode <code class="user-select-all">php artisan migrate</code>. Depois volte aqui e ative <strong>Confirmar cada pedido na loja</strong> no bloco <strong>Pedidos na vitrine</strong>.
        </div>
    @elseif ($empresa->temTelaMenu('pedidos') || $empresa->temTelaMenu('loja_online'))
        <div class="alert alert-primary border-primary-subtle small mb-3" role="alert">
            <strong>Aviso de pedido (som + janela):</strong> use o cartão <a href="#vf-config-pedidos-vitrine" class="alert-link fw-semibold">Pedidos na vitrine</a> abaixo (primeiro bloco da página), ative <strong>Confirmar cada pedido na loja</strong> e clique em <strong>Salvar</strong> no fim da página.
        </div>
    @endif
    @if (! \Illuminate\Support\Facades\Schema::hasColumn('empresas', 'instagram_url'))
        <div class="alert alert-warning small mb-3" role="alert">
            <strong>Instagram e Facebook:</strong> rode <code class="user-select-all">php artisan migrate</code> no servidor para criar as colunas no banco; até lá os links podem não ser gravados.
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h5 fw-bold mb-0">Configurações</h1>
    </div>

    <form id="vf-config-empresa-form" action="{{ route('empresa.configuracoes.update') }}" method="post" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-lg-8">
                @if ($empresa->temTelaMenu('loja_online') || $empresa->temTelaMenu('pedidos'))
                    @php
                        $temColConfPed = \Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_confirmar_pedidos');
                        $temColImpPed = \Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_impressao_pedido_habilitada');
                        $confPedVal = old('loja_confirmar_pedidos');
                        if ($confPedVal === null && $temColConfPed) {
                            $confPedVal = ($empresa->loja_confirmar_pedidos ?? false) ? '1' : '0';
                        } else {
                            $confPedVal = (string) ($confPedVal ?? '0');
                        }
                        $impPedVal = old('loja_impressao_pedido_habilitada');
                        if ($impPedVal === null && $temColImpPed) {
                            $impPedVal = ($empresa->loja_impressao_pedido_habilitada ?? true) ? '1' : '0';
                        } else {
                            $impPedVal = (string) ($impPedVal ?? '1');
                        }
                    @endphp
                    <div class="vf-card mb-3 border border-primary border-2 shadow-sm overflow-hidden rounded-2" id="vf-config-pedidos-vitrine">
                        <button type="button" class="vf-config-section-toggle w-100 d-flex align-items-center justify-content-between gap-2 btn border-0 rounded-0 py-3 px-4 text-start text-body shadow-none bg-primary-subtle bg-opacity-25" data-bs-toggle="collapse" data-bs-target="#vf-collapse-pedidos-vitrine" aria-expanded="true" aria-controls="vf-collapse-pedidos-vitrine">
                            <span class="h6 fw-bold mb-0"><i class="bi bi-receipt-cutoff text-primary me-1"></i>Pedidos na vitrine</span>
                            <i class="bi bi-chevron-down text-primary vf-config-chevron flex-shrink-0" aria-hidden="true"></i>
                        </button>
                        <div id="vf-collapse-pedidos-vitrine" class="collapse show border-top border-primary border-opacity-25">
                            <div class="p-4 pt-3">
                                <p class="small text-muted mb-3">Som tipo loja, janela no painel e confirmação manual — ative <strong>Confirmar cada pedido na loja</strong> e salve.</p>
                        @if (! $temColConfPed && ! $temColImpPed)
                            <div class="alert alert-warning mb-0 small">
                                <strong>Neste servidor as opções ainda não existem no banco.</strong> Peça para rodar <code class="user-select-all">php artisan migrate</code> na hospedagem. Depois voltando aqui você verá os botões <strong>Ativado / Desativado</strong>.
                            </div>
                        @else
                            @if ($temColConfPed)
                                <p class="small fw-semibold mb-2">Confirmar cada pedido na loja</p>
                                <p class="small text-muted mb-2">Se estiver <strong>ativado</strong>, cada pedido novo fica <strong>aguardando confirmação</strong> até você aceitar ou recusar no painel (com som até responder).</p>
                                <div class="btn-group w-100 mb-3" role="group" aria-label="Confirmar pedidos manualmente">
                                    <input type="radio" class="btn-check" name="loja_confirmar_pedidos" id="vf-loja-conf-ped-1" value="1" autocomplete="off" @checked($confPedVal === '1')>
                                    <label class="btn btn-outline-primary" for="vf-loja-conf-ped-1">Ativado</label>
                                    <input type="radio" class="btn-check" name="loja_confirmar_pedidos" id="vf-loja-conf-ped-0" value="0" autocomplete="off" @checked($confPedVal === '0')>
                                    <label class="btn btn-outline-secondary" for="vf-loja-conf-ped-0">Desativado</label>
                                </div>
                                @error('loja_confirmar_pedidos')<div class="invalid-feedback d-block mb-2">{{ $message }}</div>@enderror
                            @endif
                            @if ($temColImpPed)
                                <p class="small fw-semibold mb-2">Cupom para impressão</p>
                                <p class="small text-muted mb-2">Botões de imprimir cupom na tela do pedido. Desative se não usar impressora térmica.</p>
                                <div class="btn-group w-100" role="group" aria-label="Impressão do cupom">
                                    <input type="radio" class="btn-check" name="loja_impressao_pedido_habilitada" id="vf-loja-imp-ped-1" value="1" autocomplete="off" @checked($impPedVal === '1')>
                                    <label class="btn btn-outline-primary" for="vf-loja-imp-ped-1">Habilitada</label>
                                    <input type="radio" class="btn-check" name="loja_impressao_pedido_habilitada" id="vf-loja-imp-ped-0" value="0" autocomplete="off" @checked($impPedVal === '0')>
                                    <label class="btn btn-outline-secondary" for="vf-loja-imp-ped-0">Desabilitada</label>
                                </div>
                                @error('loja_impressao_pedido_habilitada')<div class="invalid-feedback d-block mt-2">{{ $message }}</div>@enderror
                            @endif
                        @endif
                            </div>
                        </div>
                    </div>
                @endif
                @if (\Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_aberta'))
                    @php
                        $lojaAbertaVal = old('loja_aberta');
                        if ($lojaAbertaVal === null) {
                            $lojaAbertaVal = ($empresa->loja_aberta ?? true) ? '1' : '0';
                        } else {
                            $lojaAbertaVal = (string) $lojaAbertaVal;
                        }
                    @endphp
                    <div class="vf-card mb-3 border border-primary border-2 shadow-sm overflow-hidden rounded-2">
                        <button type="button" class="vf-config-section-toggle w-100 d-flex align-items-center justify-content-between gap-2 btn border-0 rounded-0 py-3 px-4 text-start text-body shadow-none bg-primary-subtle bg-opacity-25" data-bs-toggle="collapse" data-bs-target="#vf-collapse-loja-aberta" aria-expanded="true" aria-controls="vf-collapse-loja-aberta">
                            <span class="h6 fw-bold mb-0"><i class="bi bi-shop-window text-primary me-1"></i>Loja na vitrine — aberta ou fechada</span>
                            <i class="bi bi-chevron-down text-primary vf-config-chevron flex-shrink-0" aria-hidden="true"></i>
                        </button>
                        <div id="vf-collapse-loja-aberta" class="collapse show border-top border-primary border-opacity-25">
                            <div class="p-4 pt-3">
                                <p class="small text-muted mb-3">Toque no botão para trocar. Na loja pública aparece um selo <span class="text-success fw-semibold">verde (Aberta)</span> ou <span class="text-danger fw-semibold">vermelho (Fechada)</span> ao lado do nome.</p>
                        <div class="btn-group w-100 vf-loja-aberta-group" role="group" aria-label="Loja aberta ou fechada">
                            <input type="radio" class="btn-check" name="loja_aberta" id="vf-loja-aberta-1" value="1" autocomplete="off" @checked($lojaAbertaVal === '1') required>
                            <label class="btn btn-lg btn-outline-success vf-loja-aberta-touch py-3" for="vf-loja-aberta-1"><i class="bi bi-shop-window me-2"></i>Aberta</label>
                            <input type="radio" class="btn-check" name="loja_aberta" id="vf-loja-aberta-0" value="0" autocomplete="off" @checked($lojaAbertaVal === '0')>
                            <label class="btn btn-lg btn-outline-danger vf-loja-aberta-touch py-3" for="vf-loja-aberta-0"><i class="bi bi-door-closed me-2"></i>Fechada</label>
                        </div>
                        @error('loja_aberta')<div class="invalid-feedback d-block mt-2">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                @endif
                @if (\App\Models\Empresa::schemaTemColunaLojaBannerCategoria() && $empresa->temTelaMenu('loja_online'))
                    <div class="vf-card mb-3 border border-primary border-2 shadow-sm overflow-hidden rounded-2">
                        <button type="button" class="vf-config-section-toggle w-100 d-flex align-items-center justify-content-between gap-2 btn border-0 rounded-0 py-3 px-4 text-start text-body shadow-none bg-primary-subtle bg-opacity-25" data-bs-toggle="collapse" data-bs-target="#vf-collapse-banner-cardapio" aria-expanded="true" aria-controls="vf-collapse-banner-cardapio">
                            <span class="h6 fw-bold mb-0"><i class="bi bi-image text-primary me-1"></i>Banner no cardápio</span>
                            <i class="bi bi-chevron-down text-primary vf-config-chevron flex-shrink-0" aria-hidden="true"></i>
                        </button>
                        <div id="vf-collapse-banner-cardapio" class="collapse show border-top border-primary border-opacity-25">
                            <div class="p-4 pt-3">
                                <p class="small text-muted mb-3">Bloco em destaque no topo da loja pública, <strong>antes</strong> do filtro de categorias. Ao tocar, o cliente filtra pela categoria escolhida.</p>
                        <label class="form-label" for="loja_banner_categoria_id">Categoria em destaque</label>
                        <select class="form-select form-select-sm @error('loja_banner_categoria_id') is-invalid @enderror" id="loja_banner_categoria_id" name="loja_banner_categoria_id">
                            <option value="">— Sem banner —</option>
                            @foreach (($categoriasBanner ?? collect()) as $catBanner)
                                <option value="{{ $catBanner->id }}" @selected((string) old('loja_banner_categoria_id', $empresa->loja_banner_categoria_id) === (string) $catBanner->id)>{{ $catBanner->nome }}</option>
                            @endforeach
                        </select>
                        @error('loja_banner_categoria_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <p class="small text-muted mb-0 mt-2">Só aparecem categorias <strong>ativas</strong>. Se houver foto em algum produto da categoria, ela é usada como imagem do banner.</p>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="vf-card mb-3 border border-primary border-2 shadow-sm overflow-hidden rounded-2">
                    <button type="button" class="vf-config-section-toggle w-100 d-flex align-items-center justify-content-between gap-2 btn border-0 rounded-0 py-3 px-4 text-start text-body shadow-none bg-primary-subtle bg-opacity-25" data-bs-toggle="collapse" data-bs-target="#vf-collapse-dados-empresa" aria-expanded="true" aria-controls="vf-collapse-dados-empresa">
                        <span class="h6 fw-bold mb-0"><i class="bi bi-building text-primary me-1"></i>Dados da empresa</span>
                        <i class="bi bi-chevron-down text-primary vf-config-chevron flex-shrink-0" aria-hidden="true"></i>
                    </button>
                    <div id="vf-collapse-dados-empresa" class="collapse show border-top border-primary border-opacity-25">
                        <div class="p-4 pt-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="nome">Nome / razão social</label>
                            <input type="text" class="form-control form-control-sm @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome', $empresa->nome) }}" required maxlength="255">
                            @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="slug">Slug da loja (URL pública)</label>
                            @if ($empresa->temTelaMenu('loja_online'))
                                <input type="text" class="form-control form-control-sm @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug', $empresa->slug) }}" maxlength="64" placeholder="ex.: minha-loja" autocomplete="off">
                                @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <p class="small text-muted mb-0 mt-1">Apenas letras minúsculas, números e hífens.</p>
                                @if ($empresa->slug)
                                    <p class="small mb-0 mt-2">
                                        <a href="{{ route('publico.loja', $empresa->slug) }}" target="_blank" rel="noopener">Abrir loja pública <i class="bi bi-box-arrow-up-right"></i></a>
                                    </p>
                                @endif
                            @else
                                <input type="text" class="form-control form-control-sm" id="slug" value="{{ $empresa->slug }}" disabled>
                                <p class="small text-muted mb-0 mt-1">A loja online (vitrine) não está liberada para sua empresa. Peça ao master para liberar.</p>
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
                        <div class="col-12">
                            <label class="form-label" for="logo">Logo da empresa</label>
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <div class="border rounded bg-white d-flex align-items-center justify-content-center" style="width: 84px; height: 84px; overflow: hidden;">
                                    @if ($empresa->urlLogo())
                                        <img src="{{ $empresa->urlLogo() }}" alt="Logo da empresa" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                    @else
                                        <span class="text-muted small">Sem logo</span>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file" class="form-control form-control-sm @error('logo') is-invalid @enderror" id="logo" name="logo" accept="image/png,image/jpeg,image/webp">
                                    @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <p class="small text-muted mb-0 mt-1">Formatos: JPG, PNG ou WebP. Máx: 2MB.</p>
                                </div>
                            </div>
                        </div>
                        @if (\Illuminate\Support\Facades\Schema::hasColumn('empresas', 'cep'))
                            <div class="col-md-4">
                                <label class="form-label" for="cep">CEP da loja</label>
                                @php
                                    $cepDb = $empresa->cep !== null && $empresa->cep !== '' ? preg_replace('/\D+/', '', (string) $empresa->cep) : '';
                                    $cepMostrar = strlen($cepDb) === 8 ? substr($cepDb, 0, 5).'-'.substr($cepDb, 5) : '';
                                @endphp
                                <input type="text" class="form-control form-control-sm @error('cep') is-invalid @enderror" id="cep" name="cep" value="{{ old('cep', $cepMostrar) }}" maxlength="9" placeholder="00000-000" inputmode="numeric" autocomplete="postal-code">
                                @error('cep')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <p class="small text-muted mb-0 mt-1">Usado no frete por Google (origem) junto com o endereço.</p>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" for="endereco">Endereço</label>
                                <input type="text" class="form-control form-control-sm @error('endereco') is-invalid @enderror" id="endereco" name="endereco" value="{{ old('endereco', $empresa->endereco) }}" maxlength="255" placeholder="Ex.: Av. Principal, 123 - Bairro - Cidade/UF">
                                @error('endereco')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @else
                            <div class="col-12">
                                <label class="form-label" for="endereco">Endereço</label>
                                <input type="text" class="form-control form-control-sm @error('endereco') is-invalid @enderror" id="endereco" name="endereco" value="{{ old('endereco', $empresa->endereco) }}" maxlength="255" placeholder="Ex.: Av. Principal, 123 - Bairro - Cidade/UF">
                                @error('endereco')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endif
                        <div class="col-md-6">
                            <label class="form-label" for="whatsapp">WhatsApp</label>
                            <input type="text" class="form-control form-control-sm @error('whatsapp') is-invalid @enderror" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $empresa->whatsapp) }}" maxlength="32" placeholder="Ex.: (91) 99999-9999">
                            @error('whatsapp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <p class="small text-muted mb-0 mt-1">Esse número aparece na vitrine com link direto pro WhatsApp.</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="instagram_url"><i class="bi bi-instagram text-danger me-1"></i>Instagram</label>
                            <input type="text" class="form-control form-control-sm @error('instagram_url') is-invalid @enderror" id="instagram_url" name="instagram_url" value="{{ old('instagram_url', $empresa->instagram_url) }}" maxlength="500" placeholder="https://instagram.com/sua_loja ou instagram.com/sua_loja">
                            @error('instagram_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <p class="small text-muted mb-0 mt-1">Link do perfil — ícones no topo da vitrine.</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="facebook_url"><i class="bi bi-facebook text-primary me-1"></i>Facebook</label>
                            <input type="text" class="form-control form-control-sm @error('facebook_url') is-invalid @enderror" id="facebook_url" name="facebook_url" value="{{ old('facebook_url', $empresa->facebook_url) }}" maxlength="500" placeholder="https://facebook.com/sua_pagina ou facebook.com/sua_pagina">
                            @error('facebook_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <p class="small text-muted mb-0 mt-1">Link da página — ícones no topo da vitrine.</p>
                        </div>
                        </div>
                        </div>
                    </div>
                </div>

                <div class="vf-card mb-3 border border-primary border-2 shadow-sm overflow-hidden rounded-2">
                    <button type="button" class="vf-config-section-toggle w-100 d-flex align-items-center justify-content-between gap-2 btn border-0 rounded-0 py-3 px-4 text-start text-body shadow-none bg-primary-subtle bg-opacity-25" data-bs-toggle="collapse" data-bs-target="#vf-collapse-pix-loja" aria-expanded="true" aria-controls="vf-collapse-pix-loja">
                        <span class="h6 fw-bold mb-0"><i class="bi bi-qr-code-scan text-primary me-1"></i>PIX na loja online</span>
                        <i class="bi bi-chevron-down text-primary vf-config-chevron flex-shrink-0" aria-hidden="true"></i>
                    </button>
                    <div id="vf-collapse-pix-loja" class="collapse show border-top border-primary border-opacity-25">
                        <div class="p-4 pt-3">
                    @if (! $empresa->temTelaMenu('loja_online'))
                        <p class="small text-muted mb-0">A loja online (vitrine) não está liberada para sua empresa.</p>
                    @else
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
                    @endif
                        </div>
                    </div>
                </div>

                @if (\Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_taxa_entrega_padrao') && $empresa->temTelaMenu('loja_online'))
                    <div class="vf-card mb-3 border border-primary border-2 shadow-sm overflow-hidden rounded-2">
                        <button type="button" class="vf-config-section-toggle w-100 d-flex align-items-center justify-content-between gap-2 btn border-0 rounded-0 py-3 px-4 text-start text-body shadow-none bg-primary-subtle bg-opacity-25" data-bs-toggle="collapse" data-bs-target="#vf-collapse-frete-loja" aria-expanded="true" aria-controls="vf-collapse-frete-loja">
                            <span class="h6 fw-bold mb-0"><i class="bi bi-truck text-primary me-1"></i>Frete na loja online</span>
                            <i class="bi bi-chevron-down text-primary vf-config-chevron flex-shrink-0" aria-hidden="true"></i>
                        </button>
                        <div id="vf-collapse-frete-loja" class="collapse show border-top border-primary border-opacity-25">
                            <div class="p-4 pt-3">
                        @if (\Illuminate\Support\Facades\Schema::hasTable('empresa_entregadores') && $empresa->temTelaMenu('pedidos'))
                            <p class="small text-muted border border-primary border-opacity-25 rounded px-3 py-2 mb-3 bg-primary-subtle bg-opacity-10">
                                <i class="bi bi-person-badge me-1"></i>Cadastre quem entrega com você e chame pelo WhatsApp na ordem certa, antes de apps ou terceiros:
                                <a href="{{ route('empresa.entregadores.index') }}" class="fw-semibold">Meus entregadores</a>.
                            </p>
                        @endif
                        <p class="small text-muted mb-3 mb-lg-4">Defina o valor base e, abaixo, <strong>como</strong> o sistema calcula na vitrine.</p>
                        <div class="row g-3 mb-4 pb-3 border-bottom border-primary border-opacity-25">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="loja_taxa_entrega_padrao">Taxa de entrega (R$)</label>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm @error('loja_taxa_entrega_padrao') is-invalid @enderror" id="loja_taxa_entrega_padrao" name="loja_taxa_entrega_padrao" value="{{ old('loja_taxa_entrega_padrao', $empresa->loja_taxa_entrega_padrao) }}" placeholder="Ex.: 6,00">
                                @error('loja_taxa_entrega_padrao')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <p class="small text-muted mb-0 mt-1">Usada como valor fixo ou “fallback” quando não houver outra regra.</p>
                            </div>
                            @if (\Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_permite_retirada_balcao'))
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="loja_permite_retirada_balcao">Cliente pode retirar na loja?</label>
                                    <select class="form-select form-select-sm @error('loja_permite_retirada_balcao') is-invalid @enderror" id="loja_permite_retirada_balcao" name="loja_permite_retirada_balcao">
                                        <option value="1" @selected(old('loja_permite_retirada_balcao', $empresa->loja_permite_retirada_balcao ? '1' : '0') === '1')>Sim, sem taxa</option>
                                        <option value="0" @selected(old('loja_permite_retirada_balcao', $empresa->loja_permite_retirada_balcao ? '1' : '0') === '0')>Não, só entrega</option>
                                    </select>
                                    @error('loja_permite_retirada_balcao')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @endif
                            @if (\Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_entrega_chuva_ligado'))
                                <div class="col-12 mt-2 pt-3 border-top border-primary border-opacity-25">
                                    <span class="form-label fw-semibold d-block mb-2">Chuva — acréscimo no frete de entrega</span>
                                    <div class="form-check mb-2">
                                        <input type="hidden" name="loja_entrega_chuva_ligado" value="0">
                                        <input type="checkbox" class="form-check-input" name="loja_entrega_chuva_ligado" id="loja_entrega_chuva_ligado" value="1" @checked(old('loja_entrega_chuva_ligado', $empresa->loja_entrega_chuva_ligado ? '1' : '0') === '1')>
                                        <label class="form-check-label small" for="loja_entrega_chuva_ligado">Marcar quando estiver chovendo (aplica acréscimo percentual na taxa de entrega)</label>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label small" for="loja_entrega_chuva_percentual">Acréscimo (%)</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" min="0" max="100" class="form-control @error('loja_entrega_chuva_percentual') is-invalid @enderror" id="loja_entrega_chuva_percentual" name="loja_entrega_chuva_percentual" value="{{ old('loja_entrega_chuva_percentual', $empresa->loja_entrega_chuva_percentual) }}" placeholder="Ex.: 15">
                                                <span class="input-group-text">%</span>
                                            </div>
                                            @error('loja_entrega_chuva_percentual')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            <p class="small text-muted mb-0 mt-1">Percentual sobre o valor do frete já calculado (faixa, fixo, Google ou OSRM). Não altera retirada no balcão nem frete R$ 0.</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        @if (\Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_frete_modo'))
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="loja_frete_modo">Como calcular o frete na vitrine</label>
                                <select class="form-select @error('loja_frete_modo') is-invalid @enderror" id="loja_frete_modo" name="loja_frete_modo" required>
                                    @foreach (\App\Models\Empresa::lojaFreteModosRotulos() as $val => $rotulo)
                                        <option value="{{ $val }}" @selected(old('loja_frete_modo', $empresa->loja_frete_modo ?? \App\Models\Empresa::LOJA_FRETE_FAIXAS_CEP) === $val)>{{ $rotulo }}</option>
                                    @endforeach
                                </select>
                                @error('loja_frete_modo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <p class="small text-muted mb-0 mt-2" id="vf-frete-modo-ajuda">
                                    <span class="vf-frete-ajuda vf-frete-ajuda-faixas {{ old('loja_frete_modo', $empresa->loja_frete_modo ?? \App\Models\Empresa::LOJA_FRETE_FAIXAS_CEP) === \App\Models\Empresa::LOJA_FRETE_FAIXAS_CEP ? '' : 'd-none' }}">Cadastre faixas em <a href="{{ route('empresa.loja-entrega-faixas.index') }}">Frete por CEP</a>. Fora das faixas usa a taxa acima.</span>
                                    <span class="vf-frete-ajuda vf-frete-ajuda-padrao {{ old('loja_frete_modo', $empresa->loja_frete_modo ?? '') === \App\Models\Empresa::LOJA_FRETE_PADRAO_UNICO ? '' : 'd-none' }}">Todo pedido com entrega usa só o valor em <strong>Taxa de entrega</strong>.</span>
                                    <span class="vf-frete-ajuda vf-frete-ajuda-google {{ old('loja_frete_modo', $empresa->loja_frete_modo ?? '') === \App\Models\Empresa::LOJA_FRETE_GOOGLE_DISTANCIA ? '' : 'd-none' }}">O sistema calcula km pela rota e multiplica pelo valor por km. Confira <strong>CEP e endereço da loja</strong> em <em>Dados da empresa</em>.</span>
                                    <span class="vf-frete-ajuda vf-frete-ajuda-osrm {{ old('loja_frete_modo', $empresa->loja_frete_modo ?? '') === \App\Models\Empresa::LOJA_FRETE_OSRM_DISTANCIA ? '' : 'd-none' }}">Geocoding (Nominatim) + rota OSRM entre <strong>coordenadas de origem</strong> (recomendado) ou endereço da loja e o endereço do cliente. Taxa: valor base + trechos de km acima do km incluso.</span>
                                </p>
                            </div>
                        @endif
                        @php
                            $__modoFreteForm = (string) old('loja_frete_modo', $empresa->loja_frete_modo ?? \App\Models\Empresa::LOJA_FRETE_FAIXAS_CEP);
                        @endphp
                        @if (\Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_frete_google_rs_por_km') && \Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_frete_modo') && $__modoFreteForm === \App\Models\Empresa::LOJA_FRETE_GOOGLE_DISTANCIA)
                            @php $gchk = $empresa->lojaFreteGoogleChecklistPronto(); @endphp
                            @if ($gchk['pronto'])
                                <div class="alert alert-success small py-2 mb-3"><i class="bi bi-check-circle me-1"></i>Frete por distância pronto para usar.</div>
                            @else
                                <div class="alert alert-warning small py-2 mb-3">
                                    <strong>Falta configurar:</strong>
                                    @if (! $gchk['api_configurada']) Peça ao suporte a chave Google no servidor. @endif
                                    @if (! $gchk['rs_por_km']) Preencha <strong>R$ por km</strong> abaixo. @endif
                                    @if (! $gchk['origem']) Informe endereço da loja em <em>Dados da empresa</em> ou em <strong>Saída das entregas</strong> abaixo. @endif
                                </div>
                            @endif
                        @endif
                        @if (\Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_frete_google_rs_por_km') && \Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_frete_modo') && $__modoFreteForm === \App\Models\Empresa::LOJA_FRETE_OSRM_DISTANCIA)
                            @php $ochk = $empresa->lojaFreteOsrmChecklistPronto(); @endphp
                            @if ($ochk['pronto'])
                                <div class="alert alert-success small py-2 mb-3"><i class="bi bi-check-circle me-1"></i>Frete OSRM / OpenStreetMap pronto para usar.</div>
                            @else
                                <div class="alert alert-warning small py-2 mb-3">
                                    <strong>Falta configurar:</strong>
                                    @if (! $ochk['origem']) Informe <strong>latitude e longitude de origem</strong> (preferencial), ou CEP/endereço da loja. @endif
                                    @if (! $ochk['user_agent']) Configure <code>OSM_HTTP_USER_AGENT</code> no <code>.env</code> do servidor. @endif
                                </div>
                            @endif
                        @endif
                        @if (\Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_frete_google_rs_por_km'))
                        <details class="small text-muted mb-3 border rounded px-3 py-2 bg-body-secondary bg-opacity-25">
                            <summary class="fw-semibold text-body py-1 user-select-none cursor-pointer">Ajuda técnica — Google Maps no servidor</summary>
                            <p class="mb-2 mt-2">Chave <code>GOOGLE_MAPS_API_KEY</code> no <code>.env</code>; API <strong>Distance Matrix</strong> ativa no Google Cloud.</p>
                            <p class="mb-2">
                                @if (filled(config('services.google_maps.api_key')))
                                    <span class="text-success">Neste servidor a chave está configurada.</span>
                                @else
                                    <span class="text-warning">Neste servidor a chave ainda não está configurada.</span>
                                @endif
                            </p>
                            <p class="mb-0"><code class="user-select-all">php artisan vendaffacil:google-maps-test</code></p>
                        </details>
                        <details class="small text-muted mb-3 border rounded px-3 py-2 bg-body-secondary bg-opacity-25">
                            <summary class="fw-semibold text-body py-1 user-select-none cursor-pointer">Ajuda técnica — OSRM + OpenStreetMap + Leaflet</summary>
                            <p class="mb-2 mt-2">No servidor: <code>OSRM_BASE_URL</code> (roteamento), <code>NOMINATIM_BASE_URL</code> (geocoding), <code>OSM_HTTP_USER_AGENT</code> (obrigatório para Nominatim). O mapa abaixo usa tiles OSM e Leaflet só como visual da <strong>origem</strong>.</p>
                            <p class="mb-2">
                                @if (filled(trim((string) config('services.osm_routing.http_user_agent', ''))))
                                    <span class="text-success">User-Agent OSM configurado.</span>
                                @else
                                    <span class="text-warning">Configure <code>OSM_HTTP_USER_AGENT</code> no <code>.env</code>.</span>
                                @endif
                            </p>
                            <p class="mb-0 small">Servidores públicos têm limite de uso; produção exige instância própria ou provedor.</p>
                        </details>
                            @php $__freteKmVisivel = \App\Models\Empresa::lojaFreteModoUsaKmRodoviario((string) old('loja_frete_modo', $empresa->loja_frete_modo ?? \App\Models\Empresa::LOJA_FRETE_FAIXAS_CEP)); @endphp
                            <div id="vf-frete-km-campos" class="rounded border border-primary border-opacity-50 p-3 mb-3 bg-primary-subtle bg-opacity-10 {{ $__freteKmVisivel ? '' : 'd-none' }}">
                                <h3 class="h6 fw-bold mb-2"><i class="bi bi-signpost-split text-primary me-1"></i>Frete por quilômetro rodado</h3>
                                <p class="small text-muted mb-3">No modo <strong>Google Maps</strong> use R$ por km abaixo. No modo <strong>OSRM</strong> use taxa base + km incluso + valor por km extra (e opcionalmente coordenadas fixas da origem).</p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label" for="loja_frete_google_rs_por_km">Quanto cobrar por km <span class="text-danger vf-rs-km-obr" data-vf-google-only>*</span></label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">R$</span>
                                            <input type="number" step="0.01" min="0.01" class="form-control @error('loja_frete_google_rs_por_km') is-invalid @enderror" id="loja_frete_google_rs_por_km" name="loja_frete_google_rs_por_km" value="{{ old('loja_frete_google_rs_por_km', $empresa->loja_frete_google_rs_por_km) }}" placeholder="2,50" data-vf-google-rs>
                                        </div>
                                        @error('loja_frete_google_rs_por_km')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="loja_frete_google_taxa_minima">Nunca cobrar menos que (R$)</label>
                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm @error('loja_frete_google_taxa_minima') is-invalid @enderror" id="loja_frete_google_taxa_minima" name="loja_frete_google_taxa_minima" value="{{ old('loja_frete_google_taxa_minima', $empresa->loja_frete_google_taxa_minima) }}" placeholder="Opcional">
                                        @error('loja_frete_google_taxa_minima')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="loja_frete_google_km_max">Até quantos km entrega</label>
                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm @error('loja_frete_google_km_max') is-invalid @enderror" id="loja_frete_google_km_max" name="loja_frete_google_km_max" value="{{ old('loja_frete_google_km_max', $empresa->loja_frete_google_km_max) }}" placeholder="Opcional">
                                        @error('loja_frete_google_km_max')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        <p class="small text-muted mb-0 mt-1">Vazio = sem limite.</p>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="loja_frete_origem_endereco">Saída das entregas <span class="text-muted fw-normal">(se quiser diferente do endereço da empresa)</span></label>
                                        <input type="text" class="form-control form-control-sm @error('loja_frete_origem_endereco') is-invalid @enderror" id="loja_frete_origem_endereco" name="loja_frete_origem_endereco" value="{{ old('loja_frete_origem_endereco', $empresa->loja_frete_origem_endereco) }}" maxlength="500" placeholder="Deixe em branco para usar o endereço em Dados da empresa">
                                        @error('loja_frete_origem_endereco')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    @if (\Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_entrega_lat_origem'))
                                        <div class="col-12 mt-2 pt-2 border-top border-primary border-opacity-25">
                                            <h4 class="h6 fw-semibold mb-2">Origem no mapa (OSRM) <span class="text-muted fw-normal small">— recomendado</span></h4>
                                            <p class="small text-muted mb-3">Defina as coordenadas do restaurante para o cálculo da rota sem depender só do geocode do endereço.</p>
                                            <div class="row g-2">
                                                <div class="col-md-4">
                                                    <label class="form-label small" for="loja_entrega_lat_origem">Latitude origem</label>
                                                    <input type="number" step="any" class="form-control form-control-sm @error('loja_entrega_lat_origem') is-invalid @enderror" id="loja_entrega_lat_origem" name="loja_entrega_lat_origem" value="{{ old('loja_entrega_lat_origem', $empresa->loja_entrega_lat_origem) }}" placeholder="-8.7619">
                                                    @error('loja_entrega_lat_origem')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small" for="loja_entrega_lng_origem">Longitude origem</label>
                                                    <input type="number" step="any" class="form-control form-control-sm @error('loja_entrega_lng_origem') is-invalid @enderror" id="loja_entrega_lng_origem" name="loja_entrega_lng_origem" value="{{ old('loja_entrega_lng_origem', $empresa->loja_entrega_lng_origem) }}" placeholder="-63.9039">
                                                    @error('loja_entrega_lng_origem')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small" for="loja_entrega_km_incluso">Km inclusos na taxa base</label>
                                                    <input type="number" step="0.1" min="0.1" class="form-control form-control-sm @error('loja_entrega_km_incluso') is-invalid @enderror" id="loja_entrega_km_incluso" name="loja_entrega_km_incluso" value="{{ old('loja_entrega_km_incluso', $empresa->loja_entrega_km_incluso ?? 3) }}">
                                                    @error('loja_entrega_km_incluso')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small" for="loja_entrega_valor_km_extra">R$ por km acima do incluso</label>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text">R$</span>
                                                        <input type="number" step="0.01" min="0" class="form-control @error('loja_entrega_valor_km_extra') is-invalid @enderror" id="loja_entrega_valor_km_extra" name="loja_entrega_valor_km_extra" value="{{ old('loja_entrega_valor_km_extra', $empresa->loja_entrega_valor_km_extra ?? 2) }}">
                                                    </div>
                                                    @error('loja_entrega_valor_km_extra')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                                </div>
                                                <div class="col-md-8">
                                                    <label class="form-label small" for="loja_entrega_gratis_acima_pedido">Entrega grátis acima do pedido (R$)</label>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text">R$</span>
                                                        <input type="number" step="0.01" min="0" class="form-control @error('loja_entrega_gratis_acima_pedido') is-invalid @enderror" id="loja_entrega_gratis_acima_pedido" name="loja_entrega_gratis_acima_pedido" value="{{ old('loja_entrega_gratis_acima_pedido', $empresa->loja_entrega_gratis_acima_pedido) }}" placeholder="Opcional — ex.: 100">
                                                    </div>
                                                    @error('loja_entrega_gratis_acima_pedido')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                                    <p class="small text-muted mb-0 mt-1">A taxa base fica em <strong>Taxa de entrega</strong> acima. Ex.: base R$ 5 até 3 km inclusos; cada km (ou fração) acima cobra o valor ao lado.</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    @if (($fretePreviewMapaOrigem ?? null) !== null && is_array($fretePreviewMapaOrigem))
                                    <div class="col-12 {{ $__modoFreteForm === \App\Models\Empresa::LOJA_FRETE_OSRM_DISTANCIA ? '' : 'd-none' }}" id="vf-frete-osrm-mapa-wrap">
                                        <p class="small text-muted mb-1">Mapa de referência — origem (Leaflet + tiles © OpenStreetMap)</p>
                                        <div id="vf-frete-osrm-mapa" class="rounded border" style="height:220px;" data-vf-lat="{{ $fretePreviewMapaOrigem['lat'] }}" data-vf-lon="{{ $fretePreviewMapaOrigem['lon'] }}"></div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                        <p class="small text-muted mb-0"><a href="{{ route('empresa.loja-entrega-faixas.index') }}"><i class="bi bi-geo-alt me-1"></i>Frete por CEP</a> — só vale no modo “Por CEP”.</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                <div class="vf-card mb-3 border border-primary border-2 shadow-sm overflow-hidden rounded-2">
                    <button type="button" class="vf-config-section-toggle w-100 d-flex align-items-center justify-content-between gap-2 btn border-0 rounded-0 py-3 px-4 text-start text-body shadow-none bg-primary-subtle bg-opacity-25" data-bs-toggle="collapse" data-bs-target="#vf-collapse-contrato-plano" aria-expanded="true" aria-controls="vf-collapse-contrato-plano">
                        <span class="h6 fw-bold mb-0"><i class="bi bi-file-earmark-text text-primary me-1"></i>Contrato e plano</span>
                        <i class="bi bi-chevron-down text-primary vf-config-chevron flex-shrink-0" aria-hidden="true"></i>
                    </button>
                    <div id="vf-collapse-contrato-plano" class="collapse show border-top border-primary border-opacity-25">
                        <div class="p-4 pt-3">
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
                    </div>
                </div>

                <div class="vf-card mb-3 border border-primary border-2 shadow-sm overflow-hidden rounded-2">
                    <button type="button" class="vf-config-section-toggle w-100 d-flex align-items-center justify-content-between gap-2 btn border-0 rounded-0 py-3 px-4 text-start text-body shadow-none bg-primary-subtle bg-opacity-25" data-bs-toggle="collapse" data-bs-target="#vf-collapse-modulos" aria-expanded="true" aria-controls="vf-collapse-modulos">
                        <span class="h6 fw-bold mb-0"><i class="bi bi-grid-3x3-gap text-primary me-1"></i>Módulos</span>
                        <i class="bi bi-chevron-down text-primary vf-config-chevron flex-shrink-0" aria-hidden="true"></i>
                    </button>
                    <div id="vf-collapse-modulos" class="collapse show border-top border-primary border-opacity-25">
                        <div class="p-4 pt-3">
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
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 d-none d-lg-block">Salvar alterações</button>
            </div>
        </div>

        {{-- No celular o formulário é longo (PIX, frete…): botão fixo para não precisar rolar até o fim --}}
        <div class="d-lg-none vf-config-save-mobile position-fixed bottom-0 start-0 end-0 border-top bg-body shadow-sm px-3 py-2">
            <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">Salvar alterações</button>
        </div>
        <div class="d-lg-none" style="height: 5rem;" aria-hidden="true"></div>
    </form>
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script>
            (function () {
                var sel = document.getElementById('loja_frete_modo');
                if (!sel) return;
                var box = document.getElementById('vf-frete-km-campos');
                var rs = document.querySelector('[data-vf-google-rs]');
                var rsObr = document.querySelector('.vf-rs-km-obr');
                var osrmMapWrap = document.getElementById('vf-frete-osrm-mapa-wrap');
                function sync() {
                    var v = sel.value;
                    var distKm = v === 'google_distancia' || v === 'osrm_distancia';
                    if (box) box.classList.toggle('d-none', !distKm);
                    if (rs) rs.required = (v === 'google_distancia');
                    if (rsObr) rsObr.classList.toggle('d-none', v !== 'google_distancia');
                    document.querySelectorAll('.vf-frete-ajuda').forEach(function (el) {
                        el.classList.add('d-none');
                    });
                    var map = {
                        faixas_cep: '.vf-frete-ajuda-faixas',
                        padrao_unico: '.vf-frete-ajuda-padrao',
                        google_distancia: '.vf-frete-ajuda-google',
                        osrm_distancia: '.vf-frete-ajuda-osrm'
                    };
                    var help = document.querySelector(map[v] || '');
                    if (help) help.classList.remove('d-none');
                    if (osrmMapWrap) osrmMapWrap.classList.toggle('d-none', v !== 'osrm_distancia');
                }
                sel.addEventListener('change', sync);
                sync();

                var mapEl = document.getElementById('vf-frete-osrm-mapa');
                if (mapEl && typeof L !== 'undefined') {
                    var la = parseFloat(mapEl.getAttribute('data-vf-lat'));
                    var lo = parseFloat(mapEl.getAttribute('data-vf-lon'));
                    if (!isNaN(la) && !isNaN(lo)) {
                        var m = L.map(mapEl).setView([la, lo], 15);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                            attribution: '&copy; OpenStreetMap'
                        }).addTo(m);
                        L.marker([la, lo]).addTo(m);
                        setTimeout(function () { m.invalidateSize(); }, 300);
                    }
                }
            })();
        </script>
    @endpush
@endsection
