@php
    use App\Support\Fiscal\ProdutoFiscal;
    /** @var \App\Models\Produto|null $produto */
    /** @var \App\Models\FiscalConfiguracao|null $fiscalConfig */
    $fiscalConfig = $fiscalConfig ?? null;
    $mostrarFiscal = ProdutoFiscal::empresaEmiteNota($fiscalConfig) && ProdutoFiscal::schemaTemCamposProduto();
    $p = $produto ?? null;
@endphp

@if ($mostrarFiscal)
    <div class="col-12">
        <hr class="my-2">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <h3 class="h6 fw-bold mb-0"><i class="bi bi-receipt-cutoff me-1 text-primary"></i>Fiscal (nota)</h3>
            <span class="badge text-bg-light border">Opcional — só para emissão</span>
        </div>

        @include('partials.empresa.fiscal-ajuda-guia', [
            'linkConfigFiscal' => route('empresa.fiscal.configuracoes.edit'),
        ])

        <p class="small text-muted mb-3">
            Escolha o <strong>tipo</strong> e herde os padrões da empresa.
            Detalhes (NCM, CFOP…) ficam em “Avançado”.
            <a href="{{ route('empresa.fiscal.configuracoes.edit') }}">Configurar padrões fiscais</a>
        </p>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="fiscal_tipo_item">Tipo do produto</label>
                <select class="form-select form-select-sm @error('fiscal_tipo_item') is-invalid @enderror" id="fiscal_tipo_item" name="fiscal_tipo_item">
                    <option value="">— Não definido —</option>
                    @foreach (ProdutoFiscal::tiposItemRotulos() as $val => $rot)
                        <option value="{{ $val }}" @selected(old('fiscal_tipo_item', $p?->fiscal_tipo_item) === $val)>{{ $rot }}</option>
                    @endforeach
                </select>
                @error('fiscal_tipo_item')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Produção própria = você faz (lanche, açaí). Revenda = comprou pronto (refri, água).</div>
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="fiscal_herdar_padrao" id="fiscal_herdar_padrao" value="1"
                        @checked(old('fiscal_herdar_padrao', $p?->fiscal_herdar_padrao ?? true))>
                    <label class="form-check-label" for="fiscal_herdar_padrao">Usar padrões fiscais da empresa</label>
                    <div class="form-text">Recomendado. Só desmarque se este produto for exceção.</div>
                </div>
            </div>
        </div>

        <details class="mt-3 border rounded-3 p-3 bg-light-subtle" @if ($errors->hasAny(['fiscal_ncm','fiscal_cfop','fiscal_origem','fiscal_unidade','fiscal_csosn','fiscal_cst','fiscal_cest','fiscal_gtin'])) open @endif>
            <summary class="fw-semibold user-select-none" style="cursor:pointer">Avançado — NCM, CFOP, CST/CSOSN…</summary>
            <p class="small text-muted mt-2 mb-3">Preencha só se não quiser herdar o padrão ou se o contador pediu valores específicos.</p>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small" for="fiscal_ncm">NCM</label>
                    <input type="text" class="form-control form-control-sm @error('fiscal_ncm') is-invalid @enderror" id="fiscal_ncm" name="fiscal_ncm" value="{{ old('fiscal_ncm', $p?->fiscal_ncm) }}" maxlength="16" placeholder="{{ $fiscalConfig?->padrao_ncm ?: 'Ex.: 21069090' }}">
                    @error('fiscal_ncm')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label small" for="fiscal_cfop">CFOP</label>
                    <input type="text" class="form-control form-control-sm @error('fiscal_cfop') is-invalid @enderror" id="fiscal_cfop" name="fiscal_cfop" value="{{ old('fiscal_cfop', $p?->fiscal_cfop) }}" maxlength="8" placeholder="5101 / 5102">
                    @error('fiscal_cfop')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label small" for="fiscal_origem">Origem</label>
                    <select class="form-select form-select-sm @error('fiscal_origem') is-invalid @enderror" id="fiscal_origem" name="fiscal_origem">
                        <option value="">— Padrão da empresa —</option>
                        @foreach (ProdutoFiscal::origensRotulos() as $cod => $rot)
                            <option value="{{ $cod }}" @selected(old('fiscal_origem', $p?->fiscal_origem) !== null && (string) old('fiscal_origem', $p?->fiscal_origem) === (string) $cod)>{{ $rot }}</option>
                        @endforeach
                    </select>
                    @error('fiscal_origem')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label small" for="fiscal_unidade">Unidade</label>
                    <input type="text" class="form-control form-control-sm @error('fiscal_unidade') is-invalid @enderror" id="fiscal_unidade" name="fiscal_unidade" value="{{ old('fiscal_unidade', $p?->fiscal_unidade) }}" maxlength="8" placeholder="UN">
                    @error('fiscal_unidade')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label small" for="fiscal_csosn">CSOSN (Simples)</label>
                    <input type="text" class="form-control form-control-sm @error('fiscal_csosn') is-invalid @enderror" id="fiscal_csosn" name="fiscal_csosn" value="{{ old('fiscal_csosn', $p?->fiscal_csosn) }}" maxlength="8" placeholder="102">
                    @error('fiscal_csosn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label small" for="fiscal_cst">CST (regime normal)</label>
                    <input type="text" class="form-control form-control-sm @error('fiscal_cst') is-invalid @enderror" id="fiscal_cst" name="fiscal_cst" value="{{ old('fiscal_cst', $p?->fiscal_cst) }}" maxlength="8" placeholder="Opcional">
                    @error('fiscal_cst')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label small" for="fiscal_cest">CEST</label>
                    <input type="text" class="form-control form-control-sm @error('fiscal_cest') is-invalid @enderror" id="fiscal_cest" name="fiscal_cest" value="{{ old('fiscal_cest', $p?->fiscal_cest) }}" maxlength="16" placeholder="Opcional">
                    @error('fiscal_cest')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label small" for="fiscal_gtin">GTIN / EAN (código de barras)</label>
                    <input type="text" class="form-control form-control-sm @error('fiscal_gtin') is-invalid @enderror" id="fiscal_gtin" name="fiscal_gtin" value="{{ old('fiscal_gtin', $p?->fiscal_gtin) }}" maxlength="20" placeholder="Vazio = SEM GTIN">
                    @error('fiscal_gtin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </details>
    </div>
@endif
