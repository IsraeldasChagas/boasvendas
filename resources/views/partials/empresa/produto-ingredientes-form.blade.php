{{-- $linhas: list<array{nome, foto_atual, foto_url?, id?}> ou legado list<string> só com nome --}}
<div class="col-12" id="vf-ingredientes-form">
    <label class="form-label">Ingredientes do prato <span class="text-muted fw-normal">(opcional)</span></label>
    <div id="vf-ingredientes-list" class="d-flex flex-column gap-2">
        @foreach ($linhas as $linha)
            @php
                $nome = is_array($linha) ? ($linha['nome'] ?? '') : (string) $linha;
                $fotoAtual = is_array($linha) ? ($linha['foto_atual'] ?? '') : '';
                $fotoUrl = is_array($linha) ? ($linha['foto_url'] ?? null) : null;
                $ingId = is_array($linha) ? ($linha['id'] ?? null) : null;
                if ($fotoUrl === null && $fotoAtual !== '') {
                    $fotoUrl = asset('uploads/'.ltrim(str_replace('\\', '/', $fotoAtual), '/'));
                }
            @endphp
            <div class="vf-ingrediente-row border rounded p-2 bg-light">
                <input type="hidden" name="ingrediente_ids[]" value="{{ $ingId ?? '' }}">
                <div class="row g-2 align-items-center">
                    <div class="col-md">
                        <div class="input-group">
                            <span class="input-group-text" title="Ingrediente"><i class="bi bi-plus-lg"></i></span>
                            <input type="text" name="ingrediente_nomes[]" class="form-control @error('ingrediente_nomes.*') is-invalid @enderror" value="{{ $nome }}" maxlength="120" placeholder="Nome do ingrediente" autocomplete="off">
                            <button type="button" class="btn btn-outline-danger vf-ingrediente-remover" title="Remover ingrediente"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                    <div class="col-md-auto">
                        <input type="hidden" name="ingrediente_foto_atual[]" value="{{ $fotoAtual }}" class="vf-ingrediente-foto-atual">
                        <label class="visually-hidden" for="vf-ing-foto-{{ $loop->index }}">Foto do ingrediente</label>
                        <input type="file" id="vf-ing-foto-{{ $loop->index }}" name="ingrediente_fotos[]" class="form-control form-control-sm vf-ingrediente-foto-input" accept="image/jpeg,image/png,image/webp,image/gif">
                    </div>
                </div>
                @if ($fotoUrl)
                    <div class="mt-2 ms-1 d-flex align-items-center gap-2 flex-wrap vf-ingrediente-previa-existente">
                        <img src="{{ $fotoUrl }}" alt="" class="rounded border vf-ingrediente-thumb" width="48" height="48" style="object-fit:cover;width:48px;height:48px;">
                        <span class="small text-muted">Foto atual (opcional trocar)</span>
                        <button type="button" class="btn btn-link btn-sm text-danger p-0 vf-ingrediente-limpar-foto">Remover foto</button>
                    </div>
                @endif
                <div class="vf-ingrediente-nova-previa mt-2 ms-1 d-none"></div>
            </div>
        @endforeach
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="vf-ingrediente-adicionar">
        <i class="bi bi-plus-lg me-1"></i>Adicionar ingrediente
    </button>
    @error('ingrediente_nomes')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    @error('ingrediente_nomes.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    @error('ingrediente_fotos.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    @error('ingrediente_foto_atual.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    <div class="form-text">Use os botões para incluir ou excluir linhas. Você pode enviar uma foto pequena por ingrediente (opcional). Na loja o cliente poderá pedir para retirar até o máximo indicado abaixo.</div>
</div>

<template id="vf-tpl-ingrediente">
    <div class="vf-ingrediente-row border rounded p-2 bg-light">
        <input type="hidden" name="ingrediente_ids[]" value="">
        <div class="row g-2 align-items-center">
            <div class="col-md">
                <div class="input-group">
                    <span class="input-group-text" title="Ingrediente"><i class="bi bi-plus-lg"></i></span>
                    <input type="text" name="ingrediente_nomes[]" class="form-control" maxlength="120" placeholder="Nome do ingrediente" autocomplete="off">
                    <button type="button" class="btn btn-outline-danger vf-ingrediente-remover" title="Remover ingrediente"><i class="bi bi-trash"></i></button>
                </div>
            </div>
            <div class="col-md-auto">
                <input type="hidden" name="ingrediente_foto_atual[]" value="" class="vf-ingrediente-foto-atual">
                <input type="file" name="ingrediente_fotos[]" class="form-control form-control-sm vf-ingrediente-foto-input" accept="image/jpeg,image/png,image/webp,image/gif">
            </div>
        </div>
        <div class="vf-ingrediente-nova-previa mt-2 ms-1 d-none"></div>
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

                function limparPreviaNova(row) {
                    const wrap = row.querySelector('.vf-ingrediente-nova-previa');
                    if (wrap) {
                        wrap.innerHTML = '';
                        wrap.classList.add('d-none');
                    }
                }

                function aoEscolherArquivo(ev) {
                    const input = ev.target.closest('.vf-ingrediente-foto-input');
                    if (!input || !input.files || !input.files[0]) return;
                    const row = input.closest('.vf-ingrediente-row');
                    if (!row) return;
                    const pend = row.querySelector('.vf-ingrediente-foto-atual');
                    if (pend) pend.value = '';
                    const exist = row.querySelector('.vf-ingrediente-previa-existente');
                    if (exist) exist.classList.add('d-none');
                    const wrap = row.querySelector('.vf-ingrediente-nova-previa');
                    if (!wrap) return;
                    try {
                        const url = URL.createObjectURL(input.files[0]);
                        wrap.innerHTML = '<img src="' + url + '" alt="" class="rounded border vf-ingrediente-thumb" width="48" height="48" style="object-fit:cover;width:48px;height:48px;"><span class="small text-muted ms-2">Nova foto</span>';
                        wrap.classList.remove('d-none');
                    } catch (e) {}
                }

                btnAdd.addEventListener('click', function () {
                    list.appendChild(tpl.content.cloneNode(true));
                });
                list.addEventListener('click', function (e) {
                    var clr = e.target.closest('.vf-ingrediente-limpar-foto');
                    if (clr) {
                        var row = clr.closest('.vf-ingrediente-row');
                        if (!row) return;
                        var pend = row.querySelector('.vf-ingrediente-foto-atual');
                        if (pend) pend.value = '';
                        var fi = row.querySelector('.vf-ingrediente-foto-input');
                        if (fi) fi.value = '';
                        var exist = row.querySelector('.vf-ingrediente-previa-existente');
                        if (exist) exist.classList.add('d-none');
                        limparPreviaNova(row);
                        return;
                    }
                    var del = e.target.closest('.vf-ingrediente-remover');
                    if (!del) return;
                    var row = del.closest('.vf-ingrediente-row');
                    if (row && row.parentNode) row.parentNode.removeChild(row);
                });
                list.addEventListener('change', aoEscolherArquivo);
            })();
        </script>
    @endpush
@endonce
