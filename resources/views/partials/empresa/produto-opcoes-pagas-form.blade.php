{{--
  $adicionais: collection
  $produto: ?Produto — null no cadastro novo
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
@endphp

<div class="col-12">
    <p class="fw-semibold mb-2">Opções pagas na loja</p>
    <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" name="permite_adicionais" id="permite_adicionais" value="1" @checked($permiteChecked)>
        <label class="form-check-label" for="permite_adicionais">Permitir acréscimos pagos na loja</label>
    </div>
    <input type="hidden" name="adicional_catalogo_enviado" value="1">
    <p class="small text-muted mb-2">Marque aqui <strong>só para este produto</strong> quais opções pagas (cadastradas em <a href="{{ route('empresa.adicionais.index') }}">Adicionais</a>) entram na vitrine — cada produto é independente.</p>
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
                <label class="form-label" for="acrescimo_escolhas_min">Mínimo neste produto</label>
                <input type="number" class="form-control @error('acrescimo_escolhas_min') is-invalid @enderror" id="acrescimo_escolhas_min" name="acrescimo_escolhas_min" value="{{ old('acrescimo_escolhas_min', isset($produto) ? $produto->acrescimo_escolhas_min : null) }}" min="0" max="999">
                @error('acrescimo_escolhas_min')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Total de acréscimos (soma das quantidades). Em branco = sem mínimo na vitrine.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="acrescimo_escolhas_max">Máximo neste produto</label>
                <input type="number" class="form-control @error('acrescimo_escolhas_max') is-invalid @enderror" id="acrescimo_escolhas_max" name="acrescimo_escolhas_max" value="{{ old('acrescimo_escolhas_max', isset($produto) ? $produto->acrescimo_escolhas_max : null) }}" min="0" max="999">
                @error('acrescimo_escolhas_max')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Total de acréscimos (soma das quantidades). Em branco = sem máximo na vitrine.</div>
            </div>
        </div>
    @endif
</div>
