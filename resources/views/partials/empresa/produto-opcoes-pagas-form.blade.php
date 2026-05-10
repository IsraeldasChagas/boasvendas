{{--
  $adicionais: collection
  $produto: ?Produto — null no cadastro novo
  $opcoesPagasAberto: bool — só true quando há erro de validação nesses campos (painel aberto para corrigir)
--}}
@php
    $sel = old('adicional_ids', isset($produto)
        ? $produto->adicionais->where('tipo', \App\Models\Adicional::TIPO_ACRESCENTAR)->pluck('id')->all()
        : []);
    if (! is_array($sel)) {
        $sel = [];
    }
    $permiteOld = old('permite_adicionais');
    if ($permiteOld !== null) {
        $permiteChecked = (bool) $permiteOld;
    } elseif (isset($produto)) {
        $permiteChecked = (bool) $produto->permite_adicionais;
    } else {
        $permiteChecked = false;
    }
    $acrescimosUiOld = old('acrescimos_loja_ui');
    if ($acrescimosUiOld !== null && $acrescimosUiOld !== '') {
        $acrescimosUiSel = strtolower((string) $acrescimosUiOld) === \App\Models\Produto::ACRESCIMO_LOJA_UI_CHECKBOX
            ? \App\Models\Produto::ACRESCIMO_LOJA_UI_CHECKBOX
            : \App\Models\Produto::ACRESCIMO_LOJA_UI_STEPPER;
    } elseif (isset($produto)) {
        $acrescimosUiSel = $produto->modoAcrescimosNaLoja();
    } else {
        $acrescimosUiSel = \App\Models\Produto::ACRESCIMO_LOJA_UI_STEPPER;
    }
@endphp

<div class="col-12 vf-opcoes-pagas-wrap">
    {{-- Bloco inicial: um botão até você decidir abrir --}}
    <div id="vf-adicionais-intro" class="{{ $opcoesPagasAberto ? 'd-none' : '' }}">
        <p id="vf-adicionais-produto-dica" class="small text-muted mb-2">
            As opções pagas ficam ocultas para deixar o formulário mais limpo. Clique para ver ou alterar adicionais deste produto.
        </p>
        <button type="button"
            id="vf-btn-mostrar-adicionais-produto"
            class="btn btn-outline-secondary btn-sm mb-2"
            aria-expanded="{{ $opcoesPagasAberto ? 'true' : 'false' }}"
            aria-controls="vf-opcoes-pagas-conteudo">
            <i class="bi bi-plus-circle me-1"></i>Ver adicionais disponíveis
        </button>
    </div>

    <div id="vf-opcoes-pagas-conteudo" class="{{ $opcoesPagasAberto ? '' : 'd-none' }}" role="region" aria-label="Adicionais do produto">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
            <p class="fw-semibold mb-0">Adicionais / opções pagas</p>
            <button type="button"
                id="vf-btn-recolher-adicionais-produto"
                class="btn btn-link btn-sm text-secondary py-0 text-decoration-none">
                <i class="bi bi-chevron-up me-1"></i>Recolher
            </button>
        </div>

        @if (\Illuminate\Support\Facades\Schema::hasColumn('produtos', 'acrescimos_loja_ui'))
            <fieldset class="border rounded p-3 mb-3 bg-white">
                <legend class="float-none w-auto fs-6 px-1 mb-2">Na vitrine da loja</legend>
                <p class="small text-muted mb-2 mb-md-3">Como o cliente escolhe os <strong>acréscimos pagos</strong> deste produto (opções com preço).</p>
                <div class="d-flex flex-column gap-2">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="acrescimos_loja_ui" id="acrescimos_loja_ui_stepper" value="{{ \App\Models\Produto::ACRESCIMO_LOJA_UI_STEPPER }}" @checked($acrescimosUiSel === \App\Models\Produto::ACRESCIMO_LOJA_UI_STEPPER)>
                        <label class="form-check-label" for="acrescimos_loja_ui_stepper">Botões − e + (quantidade por opção)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="acrescimos_loja_ui" id="acrescimos_loja_ui_checkbox" value="{{ \App\Models\Produto::ACRESCIMO_LOJA_UI_CHECKBOX }}" @checked($acrescimosUiSel === \App\Models\Produto::ACRESCIMO_LOJA_UI_CHECKBOX)>
                        <label class="form-check-label" for="acrescimos_loja_ui_checkbox">Caixas — marcar só o que quer (ex.: leite)</label>
                    </div>
                </div>
            </fieldset>
        @endif

        <fieldset id="vf-opcoes-pagas-fieldset" class="border-0 p-0 m-0" @disabled(!$opcoesPagasAberto)>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="permite_adicionais" id="permite_adicionais" value="1" @checked($permiteChecked)>
                <label class="form-check-label" for="permite_adicionais">Permitir acréscimos pagos na loja</label>
            </div>
            <input type="hidden" name="adicional_catalogo_enviado" value="1">
            <p class="small text-muted mb-2">Marque <strong>só para este produto</strong> quais opções (cadastradas em <a href="{{ route('empresa.adicionais.index') }}">Adicionais</a>) aparecem na vitrine.</p>
            <div class="border rounded p-3 bg-light mb-2" style="max-height: 12rem; overflow-y: auto;">
                @forelse ($adicionais->where('tipo', \App\Models\Adicional::TIPO_ACRESCENTAR) as $ad)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="adicional_ids[]" id="ad_{{ $ad->id }}" value="{{ $ad->id }}"
                            @checked(in_array($ad->id, array_map('intval', $sel), true))>
                        <label class="form-check-label" for="ad_{{ $ad->id }}">
                            {{ $ad->nome }}
                            <span class="text-muted small">(+ R$ {{ number_format((float) $ad->preco, 2, ',', '.') }})</span>
                        </label>
                    </div>
                @empty
                    <span class="small text-muted">Nenhum adicional de acréscimo cadastrado.</span>
                @endforelse
            </div>
            @error('adicional_ids')<div class="text-danger small">{{ $message }}</div>@enderror
            @error('adicional_ids.*')<div class="text-danger small">{{ $message }}</div>@enderror
            @error('permite_adicionais')<div class="text-danger small">{{ $message }}</div>@enderror
            @if (\Illuminate\Support\Facades\Schema::hasColumn('produtos', 'acrescimo_escolhas_min'))
                <div class="row g-2 mt-3">
                    <div class="col-md-4">
                        <label class="form-label" for="acrescimo_escolhas_min">Min. de ingrediente</label>
                        <input type="number" class="form-control @error('acrescimo_escolhas_min') is-invalid @enderror" id="acrescimo_escolhas_min" name="acrescimo_escolhas_min" value="{{ old('acrescimo_escolhas_min', isset($produto) ? $produto->acrescimo_escolhas_min : null) }}" min="0" max="999">
                        @error('acrescimo_escolhas_min')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Mínimo de ingredientes para acrescentar (soma das quantidades). Em branco = sem mínimo.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="acrescimo_escolhas_max">Máx. ingredientes para acrescentar</label>
                        <input type="number" class="form-control @error('acrescimo_escolhas_max') is-invalid @enderror" id="acrescimo_escolhas_max" name="acrescimo_escolhas_max" value="{{ old('acrescimo_escolhas_max', isset($produto) ? $produto->acrescimo_escolhas_max : null) }}" min="0" max="999">
                        @error('acrescimo_escolhas_max')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Máximo de ingredientes para acrescentar (soma das quantidades). Em branco = sem máximo.</div>
                    </div>
                </div>
            @endif
            <p class="small text-muted mt-2 mb-0">
                <strong>Recolher</strong> só limpa a tela. Para <strong>salvar</strong> mudanças nos adicionais, envie o formulário com este bloco <strong>aberto</strong>.
            </p>
        </fieldset>
    </div>
</div>

@once
    @push('scripts')
        <script>
            (function () {
                var wrap = document.querySelector('.vf-opcoes-pagas-wrap');
                if (!wrap) return;
                var intro = document.getElementById('vf-adicionais-intro');
                var btnMostrar = document.getElementById('vf-btn-mostrar-adicionais-produto');
                var btnRecolher = document.getElementById('vf-btn-recolher-adicionais-produto');
                var conteudo = document.getElementById('vf-opcoes-pagas-conteudo');
                var fs = document.getElementById('vf-opcoes-pagas-fieldset');
                if (!intro || !btnMostrar || !btnRecolher || !conteudo || !fs) return;

                function expandir() {
                    intro.classList.add('d-none');
                    conteudo.classList.remove('d-none');
                    fs.disabled = false;
                    btnMostrar.setAttribute('aria-expanded', 'true');
                }

                function recolher() {
                    intro.classList.remove('d-none');
                    conteudo.classList.add('d-none');
                    fs.disabled = true;
                    btnMostrar.setAttribute('aria-expanded', 'false');
                }

                btnMostrar.addEventListener('click', expandir);
                btnRecolher.addEventListener('click', recolher);
            })();
        </script>
    @endpush
@endonce
