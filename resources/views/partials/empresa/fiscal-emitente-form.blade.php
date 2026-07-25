@php
    use App\Enums\Fiscal\FiscalRegimeTributario;
    use App\Enums\Fiscal\FiscalTipoPessoa;

    $tipoPessoa = old('tipo_pessoa', $emitente->tipo_pessoa?->value ?? FiscalTipoPessoa::PessoaJuridica->value);
    $indicadorIe = old('indicador_ie', $emitente->indicador_ie ?? 'nao_contribuinte');
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Revise os dados destacados.</strong> O cadastro fiscal ainda não foi salvo.
    </div>
@endif

<div class="vf-card p-4 mb-3">
    <h3 class="h6 fw-bold mb-3"><i class="bi bi-person-vcard me-1 text-primary"></i> Identificação do emitente</h3>

    <fieldset class="mb-3">
        <legend class="form-label small fw-semibold">Forma de cadastro</legend>
        <div class="d-flex flex-wrap gap-3">
            @foreach (FiscalTipoPessoa::cases() as $tipo)
                <div class="form-check">
                    <input class="form-check-input js-tipo-pessoa" type="radio" name="tipo_pessoa"
                           id="tipo-{{ $tipo->value }}" value="{{ $tipo->value }}"
                           @checked($tipoPessoa === $tipo->value)>
                    <label class="form-check-label" for="tipo-{{ $tipo->value }}">{{ $tipo->rotulo() }}</label>
                </div>
            @endforeach
        </div>
        @error('tipo_pessoa')<div class="text-danger small">{{ $message }}</div>@enderror
    </fieldset>

    <div id="aviso-pf" class="alert alert-warning small py-2">
        <strong>Atenção:</strong> cadastrar CPF não garante autorização para emitir nota.
        Pessoa física precisa estar habilitada conforme sua atividade e as regras da SEFAZ ou prefeitura.
        Confirme com o contador antes de usar em produção.
    </div>

    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label small fw-semibold" for="rz" id="label-razao-social">Razão social</label>
            <input type="text" class="form-control form-control-sm @error('razao_social') is-invalid @enderror"
                   id="rz" name="razao_social" value="{{ old('razao_social', $emitente->razao_social) }}" required maxlength="180">
            @error('razao_social')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4 js-documento-pj">
            <label class="form-label small fw-semibold" for="cnpj">CNPJ</label>
            <input type="text" class="form-control form-control-sm @error('cnpj') is-invalid @enderror"
                   id="cnpj" name="cnpj" value="{{ old('cnpj', $emitente->cnpj) }}" maxlength="18"
                   inputmode="numeric" autocomplete="off">
            @error('cnpj')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4 js-documento-pf">
            <label class="form-label small fw-semibold" for="cpf">CPF</label>
            <input type="text" class="form-control form-control-sm @error('cpf') is-invalid @enderror"
                   id="cpf" name="cpf" value="{{ old('cpf', $emitente->cpf) }}" maxlength="14"
                   inputmode="numeric" autocomplete="off">
            @error('cpf')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 js-campo-fantasia">
            <label class="form-label small fw-semibold" for="fant">Nome fantasia</label>
            <input type="text" class="form-control form-control-sm @error('nome_fantasia') is-invalid @enderror"
                   id="fant" name="nome_fantasia" value="{{ old('nome_fantasia', $emitente->nome_fantasia) }}" maxlength="180">
            @error('nome_fantasia')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold" for="reg">Regime tributário (CRT)</label>
            <select class="form-select form-select-sm @error('regime_tributario') is-invalid @enderror" id="reg" name="regime_tributario" required>
                <option value="">— Selecione com orientação do contador —</option>
                @foreach (FiscalRegimeTributario::cases() as $regime)
                    <option value="{{ $regime->value }}" @selected((string) old('regime_tributario', $emitente->regime_tributario?->value) === $regime->value)>
                        {{ $regime->rotulo() }}
                    </option>
                @endforeach
            </select>
            @error('regime_tributario')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold" for="indicador-ie">Situação da inscrição estadual</label>
            <select class="form-select form-select-sm @error('indicador_ie') is-invalid @enderror" id="indicador-ie" name="indicador_ie" required>
                <option value="contribuinte" @selected($indicadorIe === 'contribuinte')>Contribuinte de ICMS</option>
                <option value="isento" @selected($indicadorIe === 'isento')>Isento</option>
                <option value="nao_contribuinte" @selected($indicadorIe === 'nao_contribuinte')>Não contribuinte</option>
            </select>
            @error('indicador_ie')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4" id="campo-ie">
            <label class="form-label small fw-semibold" for="ie">Inscrição estadual</label>
            <input type="text" class="form-control form-control-sm @error('inscricao_estadual') is-invalid @enderror"
                   id="ie" name="inscricao_estadual" value="{{ old('inscricao_estadual', $emitente->inscricao_estadual) }}" maxlength="32">
            @error('inscricao_estadual')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold" for="im">Inscrição municipal</label>
            <input type="text" class="form-control form-control-sm @error('inscricao_municipal') is-invalid @enderror"
                   id="im" name="inscricao_municipal" value="{{ old('inscricao_municipal', $emitente->inscricao_municipal) }}" maxlength="32">
            @error('inscricao_municipal')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Normalmente exigida para NFS-e/serviços.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold" for="telefone">Telefone fiscal</label>
            <input type="text" class="form-control form-control-sm @error('telefone') is-invalid @enderror"
                   id="telefone" name="telefone" value="{{ old('telefone', $emitente->telefone) }}" maxlength="20">
            @error('telefone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold" for="email-fiscal">E-mail fiscal</label>
            <input type="email" class="form-control form-control-sm @error('email_fiscal') is-invalid @enderror"
                   id="email-fiscal" name="email_fiscal" value="{{ old('email_fiscal', $emitente->email_fiscal) }}" maxlength="180">
            @error('email_fiscal')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="vf-card p-4 mb-3">
    <h3 class="h6 fw-bold mb-3"><i class="bi bi-geo-alt me-1 text-primary"></i> Endereço fiscal</h3>
    <p class="small text-muted">Use o endereço registrado na Receita/SEFAZ. O código IBGE identifica oficialmente o município.</p>
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label small fw-semibold" for="cep">CEP</label>
            <input type="text" class="form-control form-control-sm @error('cep') is-invalid @enderror" id="cep" name="cep"
                   value="{{ old('cep', $emitente->cep) }}" required maxlength="9" inputmode="numeric">
            @error('cep')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-7">
            <label class="form-label small fw-semibold" for="logradouro">Logradouro</label>
            <input type="text" class="form-control form-control-sm @error('logradouro') is-invalid @enderror" id="logradouro" name="logradouro"
                   value="{{ old('logradouro', $emitente->logradouro) }}" required maxlength="180">
            @error('logradouro')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold" for="numero">Número</label>
            <input type="text" class="form-control form-control-sm @error('numero') is-invalid @enderror" id="numero" name="numero"
                   value="{{ old('numero', $emitente->numero) }}" required maxlength="20">
            @error('numero')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-5">
            <label class="form-label small fw-semibold" for="complemento">Complemento</label>
            <input type="text" class="form-control form-control-sm @error('complemento') is-invalid @enderror" id="complemento" name="complemento"
                   value="{{ old('complemento', $emitente->complemento) }}" maxlength="80">
            @error('complemento')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold" for="bairro">Bairro</label>
            <input type="text" class="form-control form-control-sm @error('bairro') is-invalid @enderror" id="bairro" name="bairro"
                   value="{{ old('bairro', $emitente->bairro) }}" required maxlength="80">
            @error('bairro')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold" for="uf">UF</label>
            <input type="text" class="form-control form-control-sm text-uppercase @error('uf') is-invalid @enderror" id="uf" name="uf"
                   value="{{ old('uf', $emitente->uf) }}" required maxlength="2" placeholder="RO">
            @error('uf')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-7">
            <label class="form-label small fw-semibold" for="municipio">Município</label>
            <input type="text" class="form-control form-control-sm @error('municipio') is-invalid @enderror" id="municipio" name="municipio"
                   value="{{ old('municipio', $emitente->municipio) }}" required maxlength="100">
            @error('municipio')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-5">
            <label class="form-label small fw-semibold" for="ibge">Código IBGE do município</label>
            <input type="text" class="form-control form-control-sm @error('codigo_municipio_ibge') is-invalid @enderror" id="ibge"
                   name="codigo_municipio_ibge" value="{{ old('codigo_municipio_ibge', $emitente->codigo_municipio_ibge) }}"
                   required maxlength="7" inputmode="numeric" placeholder="1100205">
            @error('codigo_municipio_ibge')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<details class="vf-card p-4 mb-3" @if($errors->hasAny(['serie_nfce','proximo_numero_nfce','serie_nfe','proximo_numero_nfe','serie_nfse','proximo_numero_nfse','csc','csc_id'])) open @endif>
    <summary class="h6 fw-bold mb-0 user-select-none" style="cursor:pointer"><i class="bi bi-receipt me-1 text-primary"></i> Numeração e NFC-e</summary>
    <div class="row g-3 mt-1">
        @foreach (['nfce' => 'NFC-e', 'nfe' => 'NF-e', 'nfse' => 'NFS-e'] as $sigla => $rotulo)
            <div class="col-md-3">
                <label class="form-label small fw-semibold" for="serie-{{ $sigla }}">Série {{ $rotulo }}</label>
                <input type="text" class="form-control form-control-sm @error('serie_'.$sigla) is-invalid @enderror"
                       id="serie-{{ $sigla }}" name="serie_{{ $sigla }}" value="{{ old('serie_'.$sigla, $emitente->{'serie_'.$sigla}) }}" maxlength="8">
                @error('serie_'.$sigla)<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold" for="numero-{{ $sigla }}">Próximo número</label>
                <input type="number" min="1" class="form-control form-control-sm @error('proximo_numero_'.$sigla) is-invalid @enderror"
                       id="numero-{{ $sigla }}" name="proximo_numero_{{ $sigla }}" value="{{ old('proximo_numero_'.$sigla, $emitente->{'proximo_numero_'.$sigla} ?? 1) }}" required>
                @error('proximo_numero_'.$sigla)<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        @endforeach
        <div class="col-md-8">
            <label class="form-label small fw-semibold" for="csc">CSC (token da NFC-e)</label>
            <input type="text" class="form-control form-control-sm @error('csc') is-invalid @enderror" id="csc" name="csc"
                   value="{{ old('csc', $emitente->csc) }}" maxlength="120" autocomplete="off">
            @error('csc')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold" for="cscid">ID do CSC</label>
            <input type="text" class="form-control form-control-sm @error('csc_id') is-invalid @enderror" id="cscid" name="csc_id"
                   value="{{ old('csc_id', $emitente->csc_id) }}" maxlength="32" autocomplete="off">
            @error('csc_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <p class="small text-muted mt-2 mb-0">Não altere a numeração depois de começar a emitir sem orientação do contador ou do provedor fiscal.</p>
</details>

<details class="vf-card p-4 mb-3" @if($errors->hasAny(['ambiente','certificado_path','certificado_senha','emissor_tipo','api_url','api_token'])) open @endif>
    <summary class="h6 fw-bold mb-0 user-select-none" style="cursor:pointer"><i class="bi bi-plug me-1 text-primary"></i> Emissão, certificado e integração</summary>
    <div class="row g-3 mt-1">
        <div class="col-md-6">
            <label class="form-label small fw-semibold" for="amb">Ambiente</label>
            <select class="form-select form-select-sm @error('ambiente') is-invalid @enderror" id="amb" name="ambiente" required>
                @foreach (\App\Enums\Fiscal\FiscalAmbiente::cases() as $a)
                    <option value="{{ $a->value }}" @selected(old('ambiente', $emitente->ambiente?->value ?? 'homologacao') === $a->value)>{{ $a->rotulo() }}</option>
                @endforeach
            </select>
            @error('ambiente')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold" for="emissor">Emissor / driver</label>
            <select class="form-select form-select-sm @error('emissor_tipo') is-invalid @enderror" id="emissor" name="emissor_tipo" required>
                @foreach (\App\Enums\Fiscal\FiscalEmissorDriver::cases() as $d)
                    <option value="{{ $d->value }}" @selected(old('emissor_tipo', $emitente->emissor_tipo) === $d->value)>{{ $d->rotulo() }}</option>
                @endforeach
            </select>
            @error('emissor_tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold" for="certpath">Caminho privado do certificado A1</label>
            <input type="text" class="form-control form-control-sm font-monospace @error('certificado_path') is-invalid @enderror"
                   id="certpath" name="certificado_path" value="{{ old('certificado_path', $emitente->certificado_path) }}" maxlength="512"
                   placeholder="fiscal-certificados/empresa_1/cert.pfx">
            @error('certificado_path')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold" for="certsenha">Senha do certificado</label>
            <input type="password" class="form-control form-control-sm @error('certificado_senha') is-invalid @enderror"
                   id="certsenha" name="certificado_senha" maxlength="500" autocomplete="new-password"
                   placeholder="{{ $emitente->exists ? 'Deixe em branco para manter' : '' }}">
            @error('certificado_senha')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold" for="apiurl">URL da API (se aplicável)</label>
            <input type="url" class="form-control form-control-sm @error('api_url') is-invalid @enderror" id="apiurl" name="api_url"
                   value="{{ old('api_url', $emitente->api_url) }}" maxlength="512" placeholder="https://…">
            @error('api_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold" for="apitok">Token / chave da API</label>
            <input type="password" class="form-control form-control-sm font-monospace @error('api_token') is-invalid @enderror"
                   id="apitok" name="api_token" maxlength="2000" autocomplete="off"
                   placeholder="{{ $emitente->exists ? 'Deixe em branco para manter' : '' }}">
            @error('api_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</details>

<div class="form-check form-switch mb-3">
    <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo-em" @checked(old('ativo', $emitente->ativo ?? true))>
    <label class="form-check-label fw-semibold" for="ativo-em">Emitente ativo</label>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tipoInputs = document.querySelectorAll('.js-tipo-pessoa');
    const indicadorIe = document.getElementById('indicador-ie');

    function atualizarTipoPessoa() {
        const tipo = document.querySelector('.js-tipo-pessoa:checked')?.value || 'pj';
        document.querySelectorAll('.js-documento-pj').forEach(el => el.classList.toggle('d-none', tipo !== 'pj'));
        document.querySelectorAll('.js-documento-pf').forEach(el => el.classList.toggle('d-none', tipo !== 'pf'));
        document.querySelectorAll('.js-campo-fantasia').forEach(el => el.classList.toggle('d-none', tipo === 'pf'));
        document.getElementById('aviso-pf').classList.toggle('d-none', tipo !== 'pf');
        document.getElementById('label-razao-social').textContent = tipo === 'pf' ? 'Nome completo' : 'Razão social';
        document.getElementById('cnpj').required = tipo === 'pj';
        document.getElementById('cpf').required = tipo === 'pf';
    }

    function atualizarIe() {
        const contribuinte = indicadorIe.value === 'contribuinte';
        document.getElementById('campo-ie').classList.toggle('opacity-50', !contribuinte);
        document.getElementById('ie').required = contribuinte;
        document.getElementById('ie').disabled = indicadorIe.value === 'nao_contribuinte';
    }

    tipoInputs.forEach(input => input.addEventListener('change', atualizarTipoPessoa));
    indicadorIe.addEventListener('change', atualizarIe);
    atualizarTipoPessoa();
    atualizarIe();
});
</script>
