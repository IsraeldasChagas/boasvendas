<div class="vf-topbar">
    <div class="d-flex align-items-center gap-2 min-w-0">
        <button type="button" class="btn btn-light btn-sm d-lg-none border" data-vf-sidebar-toggle aria-label="Menu">
            <i class="bi bi-list"></i>
        </button>
        <h1 class="h5 mb-0 text-truncate">{{ $title }}</h1>
    </div>
    <div class="d-flex align-items-center gap-2">
        @auth
            <span class="small text-muted d-none d-md-inline">{{ Auth::user()->email }}</span>
        @endauth
        <form action="{{ route('logout') }}" method="post" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary">Sair</button>
        </form>
    </div>
</div>
