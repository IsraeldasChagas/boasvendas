<div class="vf-topbar">
    <div class="d-flex align-items-center gap-2 min-w-0">
        <button type="button" class="btn btn-light btn-sm d-lg-none border" data-vf-sidebar-toggle aria-label="Menu">
            <i class="bi bi-list"></i>
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
        <a href="{{ route('publico.loja', ['slug' => 'demo']) }}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
            <i class="bi bi-box-arrow-up-right me-1"></i>Ver vitrine
        </a>
        <form action="{{ route('logout') }}" method="post" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-primary">Sair</button>
        </form>
    </div>
</div>
