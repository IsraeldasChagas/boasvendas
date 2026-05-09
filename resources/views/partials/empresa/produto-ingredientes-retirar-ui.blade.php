{{-- $valorUi: string — Produto::ING_RETIRAR_UI_STEPPER ou CHECKBOX (mesmo name nos radios = um só escolhido) --}}
@if (\Illuminate\Support\Facades\Schema::hasColumn('produtos', 'ingredientes_retirar_ui'))
    <div class="col-12">
        <label class="form-label">Na loja: cliente marca retirada de ingredientes com</label>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="ingredientes_retirar_ui" id="ingredientes_ui_stepper"
                value="{{ \App\Models\Produto::ING_RETIRAR_UI_STEPPER }}" @checked($valorUi === \App\Models\Produto::ING_RETIRAR_UI_STEPPER)>
            <label class="form-check-label" for="ingredientes_ui_stepper">Botões − e + (quantidade por ingrediente)</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="ingredientes_retirar_ui" id="ingredientes_ui_checkbox"
                value="{{ \App\Models\Produto::ING_RETIRAR_UI_CHECKBOX }}" @checked($valorUi === \App\Models\Produto::ING_RETIRAR_UI_CHECKBOX)>
            <label class="form-check-label" for="ingredientes_ui_checkbox">Caixas de seleção (marcar o que retirar)</label>
        </div>
        <div class="form-text">Usado quando há ingredientes do prato e “máx. para retirar” maior que zero. Cada marcação em checkbox conta 1 no limite.</div>
    </div>
@endif
