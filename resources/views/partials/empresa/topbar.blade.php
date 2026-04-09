<div class="vf-topbar">
    <div class="d-flex align-items-center gap-2 min-w-0">
        <button type="button" class="btn btn-light btn-sm d-lg-none border" data-vf-sidebar-toggle aria-label="Menu">
            <i class="bi bi-list"></i>
        </button>
        <button type="button" class="btn btn-light btn-sm d-none d-lg-inline-flex border" data-vf-sidebar-collapse-toggle aria-label="Recolher menu" title="Recolher menu">
            <i class="bi bi-chevron-left"></i>
        </button>
        <div class="min-w-0">
            <h1 class="h5 mb-0 text-truncate">{{ $title }}</h1>
            <div class="small text-muted d-none d-sm-block">
                @auth
                    {{ Auth::user()->name }} · {{ Auth::user()->email }}
                @else
                    Dados de demonstração — sem backend.
                @endauth
            </div>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-shrink-0">
        <span class="vf-badge bg-success-subtle text-success d-none d-md-inline">Loja aberta</span>
        @php
            $empresa = Auth::user()?->empresa;
            $slugLoja = $empresa?->slug;
            $temLoja = $empresa?->temTelaMenu('loja_online') ?? true;
        @endphp
        @if ($empresa && $empresa->urlLogo())
            <img src="{{ $empresa->urlLogo() }}" alt="" width="84" height="84" class="rounded bg-white border d-none d-lg-inline" style="object-fit: contain;">
        @endif
        @if ($temLoja && $slugLoja)
            <a href="{{ route('publico.loja', ['slug' => $slugLoja]) }}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right me-1"></i>Ver vitrine
            </a>
        @else
            <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="{{ $temLoja ? 'Defina o slug em Configurações para habilitar a vitrine.' : 'Loja online não liberada para esta empresa.' }}">
                <i class="bi bi-box-arrow-up-right me-1"></i>Ver vitrine
            </button>
        @endif
        <form action="{{ route('logout') }}" method="post" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-primary">Sair</button>
        </form>
    </div>
</div>
