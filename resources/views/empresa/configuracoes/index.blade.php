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

    <form action="{{ route('empresa.configuracoes.update') }}" method="post" enctype="multipart/form-data">
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
                        <div class="col-12">
                            <label class="form-label" for="endereco">Endereço</label>
                            <input type="text" class="form-control form-control-sm @error('endereco') is-invalid @enderror" id="endereco" name="endereco" value="{{ old('endereco', $empresa->endereco) }}" maxlength="255" placeholder="Ex.: Av. Principal, 123 - Bairro - Cidade/UF">
                            @error('endereco')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="whatsapp">WhatsApp</label>
                            <input type="text" class="form-control form-control-sm @error('whatsapp') is-invalid @enderror" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $empresa->whatsapp) }}" maxlength="32" placeholder="Ex.: (91) 99999-9999">
                            @error('whatsapp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <p class="small text-muted mb-0 mt-1">Esse número aparece na vitrine com link direto pro WhatsApp.</p>
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
                            <p class="mb-0">
                                @if (filled(config('services.google_maps.api_key')))
                                    <span class="text-success">Status neste servidor: chave configurada.</span>
                                @else
                                    <span class="text-muted">Status neste servidor: chave ainda não configurada (<code>GOOGLE_MAPS_API_KEY</code> vazio).</span>
                                @endif
                            </p>
                        </div>
                        @if (\Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_frete_google_rs_por_km'))
                            <div id="vf-frete-google-campos" class="border rounded p-3 mb-3 bg-body-secondary bg-opacity-25 {{ old('loja_frete_modo', $empresa->loja_frete_modo ?? \App\Models\Empresa::LOJA_FRETE_FAIXAS_CEP) === \App\Models\Empresa::LOJA_FRETE_GOOGLE_DISTANCIA ? '' : 'd-none' }}">
                                <h3 class="h6 fw-bold mb-2">Modo Google Maps — valores</h3>
                                <p class="small text-muted mb-3">Obrigatório para esse modo: <strong>R$ por km</strong> e um <strong>endereço de origem</strong> (abaixo ou o campo “Endereço” da empresa, ou <code>GOOGLE_MAPS_DEFAULT_ORIGIN_ADDRESS</code> no servidor). A taxa padrão da loja é usada se faltar chave, origem ou km, ou se a API falhar.</p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label" for="loja_frete_google_rs_por_km">R$ por km rodoviário</label>
                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm @error('loja_frete_google_rs_por_km') is-invalid @enderror" id="loja_frete_google_rs_por_km" name="loja_frete_google_rs_por_km" value="{{ old('loja_frete_google_rs_por_km', $empresa->loja_frete_google_rs_por_km) }}" placeholder="Ex.: 2,50">
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
                                        <label class="form-label" for="loja_frete_origem_endereco">Endereço de origem das entregas <span class="text-muted fw-normal">(opcional)</span></label>
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

                <button type="submit" class="btn btn-primary w-100">Salvar alterações</button>
            </div>
        </div>
    </form>
    @push('scripts')
        <script>
            (function () {
                var sel = document.getElementById('loja_frete_modo');
                var box = document.getElementById('vf-frete-google-campos');
                if (!sel || !box) return;
                function sync() {
                    box.classList.toggle('d-none', sel.value !== 'google_distancia');
                }
                sel.addEventListener('change', sync);
                sync();
            })();
        </script>
    @endpush
@endsection
