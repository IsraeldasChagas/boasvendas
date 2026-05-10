<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#16a34a">
    <link rel="manifest" href="{{ asset('pwa/manifest.json') }}">
    <title>@yield('title', 'Loja') — {{ config('app.name') }}</title>
    @stack('head_meta')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="{{ asset('assets/css/vendaffacil.css') }}?v={{ @filemtime(public_path('assets/css/vendaffacil.css')) ?: time() }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="vf-body d-flex flex-column min-vh-100 bg-light {{ ($vfRodapeFluirCompra ?? false) ? 'vf-body--public-footer-flow' : 'vf-body--public-footer-fixed' }}">
    @unless ($vfOcultarNavPublica ?? false)
        @include('partials.publico.nav', ['slug' => $slug ?? 'demo', 'empresa' => $empresa ?? null])
    @endunless
    <main class="flex-grow-1 py-3{{ ($vfOcultarNavPublica ?? false) ? ' pt-4' : '' }}">
        @if (session('status'))
            <div class="container"><div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div></div>
        @endif
        @if (session('warning'))
            <div class="container"><div class="alert alert-warning alert-dismissible fade show" role="alert">
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div></div>
        @endif
        @if (! empty($slug) && ! empty($vfPassoCompra))
            <div class="container">
                @include('partials.publico.compra-fluxo-etapas', [
                    'slug' => $slug,
                    'passoAtual' => $vfPassoCompra,
                    'pedidoShowUrl' => $vfPedidoShowUrl ?? null,
                ])
            </div>
        @endif
        @yield('content')
    </main>
    @include('partials.publico.footer')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="{{ asset('assets/js/vendaffacil.js') }}?v={{ @filemtime(public_path('assets/js/vendaffacil.js')) ?: time() }}"></script>
    @stack('scripts')
</body>
</html>
