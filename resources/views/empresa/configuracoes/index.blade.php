@extends('layouts.empresa')

@section('title', 'Configurações')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('empresa.dashboard')],
        ['label' => 'Configurações', 'url' => route('empresa.configuracoes.index')],
    ]])

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
            <strong>Onde ativar aviso de pedido (som + janela):</strong> role esta página até o cartão <strong>Pedidos na vitrine</strong>, coloque <strong>Confirmar cada pedido na loja</strong> em <strong>Ativado</strong> e clique em <strong>Salvar</strong> no fim da página. Isso obriga aceitar ou recusar cada pedido novo e dispara o alerta no painel.
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
                @if (\Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_aberta'))
                    @php
                        $lojaAbertaVal = old('loja_aberta');
                        if ($lojaAbertaVal === null) {
                            $lojaAbertaVal = ($empresa->loja_aberta ?? true) ? '1' : '0';
                        } else {
                            $lojaAbertaVal = (string) $lojaAbertaVal;
                        }
                    @endphp
                    <div class="vf-card p-4 mb-3 vf-loja-status-card">
                        <h2 class="h6 fw-bold mb-2">Loja na vitrine — aberta ou fechada</h2>
                        <p class="small text-muted mb-3">Toque no botão para trocar. Na loja pública aparece um selo <span class="text-success fw-semibold">verde (Aberta)</span> ou <span class="text-danger fw-semibold">vermelho (Fechada)</span> ao lado do nome.</p>
                        <div class="btn-group w-100 vf-loja-aberta-group" role="group" aria-label="Loja aberta ou fechada">
                            <input type="radio" class="btn-check" name="loja_aberta" id="vf-loja-aberta-1" value="1" autocomplete="off" @checked($lojaAbertaVal === '1') required>
                            <label class="btn btn-lg btn-outline-success vf-loja-aberta-touch py-3" for="vf-loja-aberta-1"><i class="bi bi-shop-window me-2"></i>Aberta</label>
                            <input type="radio" class="btn-check" name="loja_aberta" id="vf-loja-aberta-0" value="0" autocomplete="off" @checked($lojaAbertaVal === '0')>
                            <label class="btn btn-lg btn-outline-danger vf-loja-aberta-touch py-3" for="vf-loja-aberta-0"><i class="bi bi-door-closed me-2"></i>Fechada</label>
                        </div>
                        @error('loja_aberta')<div class="invalid-feedback d-block mt-2">{{ $message }}</div>@enderror
                    </div>
                @endif
                @if (($empresa->temTelaMenu('loja_online') || $empresa->temTelaMenu('pedidos')) && (\Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_confirmar_pedidos') || \Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_impressao_pedido_habilitada')))
                    @php
                        $confPedVal = old('loja_confirmar_pedidos');
                        if ($confPedVal === null && \Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_confirmar_pedidos')) {
                            $confPedVal = ($empresa->loja_confirmar_pedidos ?? false) ? '1' : '0';
                        } else {
                            $confPedVal = (string) ($confPedVal ?? '0');
                        }
                        $impPedVal = old('loja_impressao_pedido_habilitada');
                        if ($impPedVal === null && \Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_impressao_pedido_habilitada')) {
                            $impPedVal = ($empresa->loja_impressao_pedido_habilitada ?? true) ? '1' : '0';
                        } else {
                            $impPedVal = (string) ($impPedVal ?? '1');
                        }
                    @endphp
                    <div class="vf-card p-4 mb-3">
                        <h2 class="h6 fw-bold mb-2">Pedidos na vitrine</h2>
                        <p class="small text-muted mb-3">Controle como novos pedidos entram no painel e se o cupom para impressora térmica fica disponível.</p>
                        @if (\Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_confirmar_pedidos'))
                            <p class="small fw-semibold mb-2">Confirmar cada pedido na loja</p>
                            <p class="small text-muted mb-2">Se estiver <strong>ativado</strong>, o pedido fica <strong>aguardando confirmação</strong> até você aceitar ou recusar no painel. O cliente vê que a loja ainda não confirmou.</p>
                            <div class="btn-group w-100 mb-3" role="group" aria-label="Confirmar pedidos manualmente">
                                <input type="radio" class="btn-check" name="loja_confirmar_pedidos" id="vf-loja-conf-ped-1" value="1" autocomplete="off" @checked($confPedVal === '1')>
                                <label class="btn btn-outline-primary" for="vf-loja-conf-ped-1">Ativado</label>
                                <input type="radio" class="btn-check" name="loja_confirmar_pedidos" id="vf-loja-conf-ped-0" value="0" autocomplete="off" @checked($confPedVal === '0')>
                                <label class="btn btn-outline-secondary" for="vf-loja-conf-ped-0">Desativado</label>
                            </div>
                            @error('loja_confirmar_pedidos')<div class="invalid-feedback d-block mb-2">{{ $message }}</div>@enderror
                        @endif
                        @if (\Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_impressao_pedido_habilitada'))
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
                    </div>
                @endif
                @if (\App\Models\Empresa::schemaTemColunaLojaBannerCategoria() && $empresa->temTelaMenu('loja_online'))
                    <div class="vf-card p-4 mb-3">
                        <h2 class="h6 fw-bold mb-2">Banner no cardápio</h2>
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
                @endif
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

                <div class="vf-card p-4 mb-3">
                    <h2 class="h6 fw-bold mb-3">PIX na loja online</h2>
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

                @if (\Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_taxa_entrega_padrao') && $empresa->temTelaMenu('loja_online'))
                    <div class="vf-card p-4 mb-3">
                        <h2 class="h6 fw-bold mb-3">Frete na loja online</h2>
                        @if (\Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_frete_modo'))
                            <div class="mb-3">
                                <label class="form-label" for="loja_frete_modo">Como calcular o frete</label>
                                <select class="form-select form-select-sm @error('loja_frete_modo') is-invalid @enderror" id="loja_frete_modo" name="loja_frete_modo" required>
                                    @foreach (\App\Models\Empresa::lojaFreteModosRotulos() as $val => $rotulo)
                                        <option value="{{ $val }}" @selected(old('loja_frete_modo', $empresa->loja_frete_modo ?? \App\Models\Empresa::LOJA_FRETE_FAIXAS_CEP) === $val)>{{ $rotulo }}</option>
                                    @endforeach
                                </select>
                                @error('loja_frete_modo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <p class="small text-muted mb-0 mt-2">Troque o modo quando quiser. No modo <strong>Google Maps</strong>, o frete usa a rota de carro entre o endereço de origem da loja e o endereço do cliente.</p>
                            </div>
                        @endif
                        <div class="alert alert-light border small mb-3 mb-md-3 py-2 px-3">
                            <strong class="d-block mb-1">Google Maps (API no servidor)</strong>
                            <p class="mb-2">A chave fica no <code>.env</code> do servidor: <code>GOOGLE_MAPS_API_KEY</code>. No <a href="https://console.cloud.google.com/apis/library" rel="noopener noreferrer" target="_blank">Google Cloud Console</a> habilite a <strong>Distance Matrix API</strong> (obrigatória para o cálculo). <strong>Maps JavaScript API</strong> só se for exibir mapa no site.</p>
                            <p class="mb-2">
                                @if (filled(config('services.google_maps.api_key')))
                                    <span class="text-success">Status neste servidor: chave configurada.</span>
                                @else
                                    <span class="text-muted">Status neste servidor: chave ainda não configurada (<code>GOOGLE_MAPS_API_KEY</code> vazio).</span>
                                @endif
                            </p>
                            <p class="mb-0 small">Teste no terminal (no projeto): <code class="user-select-all">php artisan vendaffacil:google-maps-test</code></p>
                        </div>
                        @if (\Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_frete_google_rs_por_km') && \Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_frete_modo') && $empresa->lojaFreteModoEfetivo() === \App\Models\Empresa::LOJA_FRETE_GOOGLE_DISTANCIA)
                            @php $gchk = $empresa->lojaFreteGoogleChecklistPronto(); @endphp
                            @if ($gchk['pronto'])
                                <div class="alert alert-success small py-2 mb-3"><strong>Frete Google:</strong> pronto para uso (chave no servidor, R$/km e endereço de origem).</div>
                            @else
                                <div class="alert alert-warning small py-2 mb-3">
                                    <strong class="d-block mb-1">Frete Google — falta algo:</strong>
                                    <ul class="small mb-0 ps-3">
                                        @if (! $gchk['api_configurada'])
                                            <li>Chave <code>GOOGLE_MAPS_API_KEY</code> no <code>.env</code> do servidor</li>
                                        @endif
                                        @if (! $gchk['rs_por_km'])
                                            <li>Valor em <strong>R$ por km</strong> (maior que zero)</li>
                                        @endif
                                        @if (! $gchk['origem'])
                                            <li><strong>Origem da rota</strong>: CEP da loja, endereço de origem do frete, Endereço da empresa ou <code>GOOGLE_MAPS_DEFAULT_ORIGIN_ADDRESS</code></li>
                                        @endif
                                    </ul>
                                </div>
                            @endif
                        @endif
                        @if (\Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_frete_google_rs_por_km'))
                            <div id="vf-frete-google-campos" class="border rounded p-3 mb-3 bg-body-secondary bg-opacity-25 {{ old('loja_frete_modo', $empresa->loja_frete_modo ?? \App\Models\Empresa::LOJA_FRETE_FAIXAS_CEP) === \App\Models\Empresa::LOJA_FRETE_GOOGLE_DISTANCIA ? '' : 'd-none' }}">
                                <h3 class="h6 fw-bold mb-2">Modo Google Maps — valores</h3>
                                <p class="small text-muted mb-3">Com este modo ativo, o sistema <strong>exige</strong> salvar: chave no servidor, <strong>R$ por km</strong> &gt; 0 e pelo menos uma <strong>origem</strong> (<strong>CEP</strong> da loja em Dados da empresa, campo abaixo, “Endereço” ou <code>GOOGLE_MAPS_DEFAULT_ORIGIN_ADDRESS</code>). Se a API falhar no checkout, ainda entra a taxa padrão da loja.</p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label" for="loja_frete_google_rs_por_km">R$ por km rodoviário <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="0.01" class="form-control form-control-sm @error('loja_frete_google_rs_por_km') is-invalid @enderror" id="loja_frete_google_rs_por_km" name="loja_frete_google_rs_por_km" value="{{ old('loja_frete_google_rs_por_km', $empresa->loja_frete_google_rs_por_km) }}" placeholder="Ex.: 2,50" data-vf-google-rs>
                                        @error('loja_frete_google_rs_por_km')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="loja_frete_google_taxa_minima">Taxa mínima (R$) <span class="text-muted fw-normal">(opcional)</span></label>
                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm @error('loja_frete_google_taxa_minima') is-invalid @enderror" id="loja_frete_google_taxa_minima" name="loja_frete_google_taxa_minima" value="{{ old('loja_frete_google_taxa_minima', $empresa->loja_frete_google_taxa_minima) }}" placeholder="Ex.: 8,00">
                                        @error('loja_frete_google_taxa_minima')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="loja_frete_google_km_max">Km máximo <span class="text-muted fw-normal">(opcional)</span></label>
                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm @error('loja_frete_google_km_max') is-invalid @enderror" id="loja_frete_google_km_max" name="loja_frete_google_km_max" value="{{ old('loja_frete_google_km_max', $empresa->loja_frete_google_km_max) }}" placeholder="Ex.: 15">
                                        @error('loja_frete_google_km_max')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        <p class="small text-muted mb-0 mt-1">Acima disso o cliente não consegue finalizar entrega.</p>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="loja_frete_origem_endereco">Endereço de origem das entregas <span class="text-muted fw-normal">(se vazio, usa “Endereço” da empresa ou o .env)</span></label>
                                        <input type="text" class="form-control form-control-sm @error('loja_frete_origem_endereco') is-invalid @enderror" id="loja_frete_origem_endereco" name="loja_frete_origem_endereco" value="{{ old('loja_frete_origem_endereco', $empresa->loja_frete_origem_endereco) }}" maxlength="500" placeholder="Ex.: Rua X, 100 — Bairro, Cidade - UF">
                                        @error('loja_frete_origem_endereco')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        <p class="small text-muted mb-0 mt-1">Se vazio, usa o endereço cadastrado acima em “Dados da empresa” ou o padrão global do servidor.</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <p class="small text-muted mb-3">No modo <strong>faixas</strong>, a taxa padrão vale quando o CEP do cliente <strong>não</strong> cai em nenhuma faixa em <a href="{{ route('empresa.loja-entrega-faixas.index') }}">Frete por CEP</a>. No modo <strong>só taxa padrão</strong> ou <strong>Google Maps</strong>, as faixas são ignoradas. Deixe a taxa padrão em branco para usar o global (<code>VENDAFFACIL_TAXA_ENTREGA</code>).</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="loja_taxa_entrega_padrao">Taxa padrão de entrega (R$)</label>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm @error('loja_taxa_entrega_padrao') is-invalid @enderror" id="loja_taxa_entrega_padrao" name="loja_taxa_entrega_padrao" value="{{ old('loja_taxa_entrega_padrao', $empresa->loja_taxa_entrega_padrao) }}" placeholder="Ex.: 6,00">
                                @error('loja_taxa_entrega_padrao')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            @if (\Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_permite_retirada_balcao'))
                                <div class="col-md-6">
                                    <label class="form-label" for="loja_permite_retirada_balcao">Retirada no balcão</label>
                                    <select class="form-select form-select-sm @error('loja_permite_retirada_balcao') is-invalid @enderror" id="loja_permite_retirada_balcao" name="loja_permite_retirada_balcao">
                                        <option value="1" @selected(old('loja_permite_retirada_balcao', $empresa->loja_permite_retirada_balcao ? '1' : '0') === '1')>Sim — cliente pode retirar sem taxa</option>
                                        <option value="0" @selected(old('loja_permite_retirada_balcao', $empresa->loja_permite_retirada_balcao ? '1' : '0') === '0')>Não — só entrega</option>
                                    </select>
                                    @error('loja_permite_retirada_balcao')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
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
        <script>
            (function () {
                var sel = document.getElementById('loja_frete_modo');
                var box = document.getElementById('vf-frete-google-campos');
                var rs = document.querySelector('[data-vf-google-rs]');
                if (!sel || !box) return;
                function sync() {
                    var google = sel.value === 'google_distancia';
                    box.classList.toggle('d-none', !google);
                    if (rs) rs.required = google;
                }
                sel.addEventListener('change', sync);
                sync();
            })();
        </script>
    @endpush
@endsection
