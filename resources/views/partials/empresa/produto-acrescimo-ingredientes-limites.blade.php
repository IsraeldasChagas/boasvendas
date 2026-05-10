{{--
  Limites de acréscimos na vitrine (soma das quantidades por opção paga).
  Fica junto da lista de ingredientes para ficar sempre visível no formulário.
  $produto: ?Produto
--}}
@if (\Illuminate\Support\Facades\Schema::hasColumn('produtos', 'acrescimo_escolhas_min'))
    <div class="col-12">
        <p class="small text-muted mb-2 mb-md-0">Limites para <strong>acrescentar</strong> (adicionais pagos; não é retirada)</p>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="acrescimo_escolhas_min">Min. de ingrediente</label>
        <input type="number" class="form-control @error('acrescimo_escolhas_min') is-invalid @enderror" id="acrescimo_escolhas_min" name="acrescimo_escolhas_min" value="{{ old('acrescimo_escolhas_min', isset($produto) ? $produto->acrescimo_escolhas_min : null) }}" min="0" max="999">
        @error('acrescimo_escolhas_min')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Mínimo para acrescentar (soma das quantidades). Em branco = sem mínimo.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="acrescimo_escolhas_max">Máx. ingredientes para acrescentar</label>
        <input type="number" class="form-control @error('acrescimo_escolhas_max') is-invalid @enderror" id="acrescimo_escolhas_max" name="acrescimo_escolhas_max" value="{{ old('acrescimo_escolhas_max', isset($produto) ? $produto->acrescimo_escolhas_max : null) }}" min="0" max="999">
        @error('acrescimo_escolhas_max')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Máximo para acrescentar (soma das quantidades). Em branco = sem máximo.</div>
    </div>
@endif
