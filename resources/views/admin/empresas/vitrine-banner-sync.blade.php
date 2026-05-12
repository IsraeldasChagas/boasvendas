@extends('layouts.admin')

@section('title', 'Sincronizar vitrine (banner)')

@section('content')
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="vf-card p-4 mb-4">
        <p class="text-muted small mb-0">
            Copia as <strong>imagens do banner</strong> da loja pública e, quando existir na base, a <strong>categoria em destaque</strong> da vitrine.
            A categoria só é aplicada na empresa destino se existir uma <strong>categoria ativa com o mesmo nome</strong> (comparação sem diferenciar maiúsculas/minúsculas).
        </p>
    </div>

    <div class="vf-card p-4">
        <form method="post" action="{{ route('admin.empresas.vitrine-banner-sync.store') }}" id="form-vitrine-banner-sync">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-medium" for="origem_id">Empresa origem</label>
                <select class="form-select @error('origem_id') is-invalid @enderror" id="origem_id" name="origem_id" required>
                    <option value="" disabled @selected((string) old('origem_id', request('origem_id')) === '')>Selecione…</option>
                    @foreach ($empresas as $e)
                        <option value="{{ $e->id }}" @selected((string) old('origem_id', request('origem_id')) === (string) $e->id)>{{ $e->nome }} (#{{ $e->id }})</option>
                    @endforeach
                </select>
                @error('origem_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <fieldset class="mb-3">
                <legend class="form-label fw-medium mb-2">Empresas destino</legend>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="alvos" id="alvos-todas" value="todas" @checked(old('alvos', 'todas') === 'todas')>
                    <label class="form-check-label" for="alvos-todas">Todas as outras empresas</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="alvos" id="alvos-escolhidas" value="escolhidas" @checked(old('alvos') === 'escolhidas')>
                    <label class="form-check-label" for="alvos-escolhidas">Escolher empresas</label>
                </div>
                @error('alvos')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </fieldset>

            <div class="mb-3 {{ old('alvos') === 'escolhidas' ? '' : 'd-none' }}" id="wrap-empresa-ids">
                <label class="form-label small text-muted" for="empresa_ids">Destinos (mantenha Ctrl/Cmd para várias)</label>
                <select class="form-select @error('empresa_ids') is-invalid @enderror @error('empresa_ids.*') is-invalid @enderror" id="empresa_ids" name="empresa_ids[]" multiple size="10">
                    @foreach ($empresas as $e)
                        <option value="{{ $e->id }}" @selected(collect(old('empresa_ids', []))->contains((string) $e->id))>{{ $e->nome }} (#{{ $e->id }})</option>
                    @endforeach
                </select>
                @error('empresa_ids')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                @error('empresa_ids.*')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" value="1" id="substituir_imagens_existentes" name="substituir_imagens_existentes" @checked(old('substituir_imagens_existentes'))>
                <label class="form-check-label" for="substituir_imagens_existentes">
                    Substituir imagens de banner já existentes nas empresas destino
                </label>
                <div class="form-text">Se desmarcado, só copia imagens para empresas que ainda não têm nenhuma imagem de banner.</div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-arrow-repeat me-1"></i>Sincronizar
                </button>
                <a href="{{ route('admin.empresas.index') }}" class="btn btn-outline-secondary">Voltar às empresas</a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var todas = document.getElementById('alvos-todas');
            var esc = document.getElementById('alvos-escolhidas');
            var wrap = document.getElementById('wrap-empresa-ids');
            var sel = document.getElementById('empresa_ids');
            function sync() {
                if (!wrap || !sel) return;
                var show = esc && esc.checked;
                wrap.classList.toggle('d-none', !show);
                if (!show) {
                    Array.prototype.forEach.call(sel.options, function (o) { o.selected = false; });
                }
            }
            if (todas) todas.addEventListener('change', sync);
            if (esc) esc.addEventListener('change', sync);
            sync();
        })();
    </script>
@endpush
