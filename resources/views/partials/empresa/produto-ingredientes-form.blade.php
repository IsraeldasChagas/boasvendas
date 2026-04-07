{{-- $linhas: list<string> nomes já preenchidos (pode ser vazio) --}}
<div class="col-12" id="vf-ingredientes-form">
    <label class="form-label">Ingredientes do prato <span class="text-muted fw-normal">(opcional)</span></label>
    <div id="vf-ingredientes-list" class="d-flex flex-column gap-2">
        @foreach ($linhas as $nome)
            <div class="input-group vf-ingrediente-row">
                <span class="input-group-text" title="Ingrediente"><i class="bi bi-plus-lg"></i></span>
                <input type="text" name="ingrediente_nomes[]" class="form-control @error('ingrediente_nomes.*') is-invalid @enderror" value="{{ $nome }}" maxlength="120" placeholder="Nome do ingrediente" autocomplete="off">
                <button type="button" class="btn btn-outline-danger vf-ingrediente-remover" title="Remover ingrediente"><i class="bi bi-trash"></i></button>
            </div>
        @endforeach
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="vf-ingrediente-adicionar">
        <i class="bi bi-plus-lg me-1"></i>Adicionar ingrediente
    </button>
    @error('ingrediente_nomes')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    @error('ingrediente_nomes.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    <div class="form-text">Use os botões para incluir ou excluir linhas. Na loja o cliente poderá pedir para retirar até o máximo indicado abaixo.</div>
</div>

<template id="vf-tpl-ingrediente">
    <div class="input-group vf-ingrediente-row">
        <span class="input-group-text" title="Ingrediente"><i class="bi bi-plus-lg"></i></span>
        <input type="text" name="ingrediente_nomes[]" class="form-control" maxlength="120" placeholder="Nome do ingrediente" autocomplete="off">
        <button type="button" class="btn btn-outline-danger vf-ingrediente-remover" title="Remover ingrediente"><i class="bi bi-trash"></i></button>
    </div>
</template>

@once
    @push('scripts')
        <script>
            (function () {
                const list = document.getElementById('vf-ingredientes-list');
                const btnAdd = document.getElementById('vf-ingrediente-adicionar');
                const tpl = document.getElementById('vf-tpl-ingrediente');
                if (!list || !btnAdd || !tpl) return;
                btnAdd.addEventListener('click', function () {
                    list.appendChild(tpl.content.cloneNode(true));
                });
                list.addEventListener('click', function (e) {
                    var del = e.target.closest('.vf-ingrediente-remover');
                    if (!del) return;
                    var row = del.closest('.vf-ingrediente-row');
                    if (row && row.parentNode) row.parentNode.removeChild(row);
                });
            })();
        </script>
    @endpush
@endonce
