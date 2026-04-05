<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#16a34a">
    <link rel="manifest" href="{{ asset('pwa/manifest.json') }}">
    <title>@yield('title', 'Loja') — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="{{ asset('assets/css/boasvandas.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="vf-body d-flex flex-column min-vh-100 bg-light">
    @include('partials.publico.nav', ['slug' => $slug ?? 'demo', 'empresa' => $empresa ?? null])
    <main class="flex-grow-1 py-3">
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
        @yield('content')
    </main>
    @include('partials.publico.footer')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="{{ asset('assets/js/boasvandas.js') }}"></script>
    @stack('scripts')
</body>
</html>
