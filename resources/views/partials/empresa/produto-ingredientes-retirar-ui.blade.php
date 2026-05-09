{{--
  $valorUi: string — Produto::ING_RETIRAR_UI_STEPPER ou CHECKBOX
  $mostrarAvisoSemIngredientesRetirar: opcional bool — true = alerta (sem linhas em "Ingredientes do prato")
--}}
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
        <div class="form-text">
            Vale só para o bloco <strong>Ingredientes para retirar</strong> na loja (itens que você cadastra em <strong>Ingredientes do prato</strong>, abaixo, com máximo para retirar &gt; 0).
            Os <strong>adicionais pagos</strong> (ex.: creme de leite em “Ver adicionais disponíveis”) continuam sempre com botões − e + para quantidade — esta opção <strong>não</strong> os altera.
        </div>
        @if (! empty($mostrarAvisoSemIngredientesRetirar))
            <div class="alert alert-warning mt-2 mb-0 py-2 small" role="status">
                Ainda não há <strong>ingredientes do prato</strong> neste produto. Por isso na vitrine não aparece retirada em caixa nem em −/+;
                só vê os <strong>adicionais pagos</strong>. Adicione pelo menos uma linha em “Ingredientes do prato”, defina o máximo para retirar e salve.
            </div>
        @endif
    </div>
@endif
