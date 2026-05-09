{{--
  $valorUi: string — Produto::ING_RETIRAR_UI_STEPPER ou CHECKBOX
  $section: 'hidden' — só o input hidden (colocar no início do <form>)
            'radios' — rótulos + radios (sem name; o valor vai no hidden acima)
--}}
@php
    $sec = $section ?? 'radios';
@endphp
@if (\Illuminate\Support\Facades\Schema::hasColumn('produtos', 'ingredientes_retirar_ui'))
    @if ($sec === 'hidden')
        <input type="hidden" name="ingredientes_retirar_ui" id="vf-input-ing-retirar-ui" value="{{ $valorUi }}">
    @elseif ($sec === 'radios')
        <div class="col-12">
            <label class="form-label">Na loja: cliente marca retirada de ingredientes com</label>
            <div class="form-check">
                <input class="form-check-input vf-ing-retirar-ui-radio" type="radio" id="ingredientes_ui_stepper"
                    value="{{ \App\Models\Produto::ING_RETIRAR_UI_STEPPER }}" @checked($valorUi === \App\Models\Produto::ING_RETIRAR_UI_STEPPER)>
                <label class="form-check-label" for="ingredientes_ui_stepper">Botões − e + (quantidade por ingrediente)</label>
            </div>
            <div class="form-check">
                <input class="form-check-input vf-ing-retirar-ui-radio" type="radio" id="ingredientes_ui_checkbox"
                    value="{{ \App\Models\Produto::ING_RETIRAR_UI_CHECKBOX }}" @checked($valorUi === \App\Models\Produto::ING_RETIRAR_UI_CHECKBOX)>
                <label class="form-check-label" for="ingredientes_ui_checkbox">Caixas de seleção (marcar o que retirar)</label>
            </div>
            <div class="form-text">Usado quando há ingredientes do prato e “máx. para retirar” maior que zero. Cada marcação em checkbox conta 1 no limite.</div>
        </div>
    @endif
@endif

@if (\Illuminate\Support\Facades\Schema::hasColumn('produtos', 'ingredientes_retirar_ui') && ($sec === 'radios'))
    @once
        @push('scripts')
            <script>
                (function () {
                    function syncIngRetirarUiHidden() {
                        var hidden = document.getElementById('vf-input-ing-retirar-ui');
                        if (!hidden) return;
                        document.querySelectorAll('.vf-ing-retirar-ui-radio').forEach(function (r) {
                            if (r.checked) hidden.value = r.value;
                        });
                    }
                    document.addEventListener('DOMContentLoaded', function () {
                        document.querySelectorAll('.vf-ing-retirar-ui-radio').forEach(function (r) {
                            r.addEventListener('change', syncIngRetirarUiHidden);
                        });
                        syncIngRetirarUiHidden();
                    });
                })();
            </script>
        @endpush
    @endonce
@endif
