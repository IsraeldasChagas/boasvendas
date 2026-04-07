<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Página não encontrada — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="{{ asset('assets/css/vendaffacil.css') }}" rel="stylesheet">
</head>
<body class="vf-body bg-light min-vh-100 d-flex flex-column">
    <main class="flex-grow-1 d-flex align-items-center py-5">
        <div class="container">
            <div class="vf-card p-4 p-md-5 text-center mx-auto" style="max-width: 34rem;">
                <p class="display-6 text-primary fw-bold mb-2">404</p>
                <h1 class="h4 fw-bold mb-3">Página não encontrada</h1>
                <p class="text-muted mb-4">
                    @php
                        $msg = isset($exception) ? (string) $exception->getMessage() : '';
                        $hideRaw = $msg !== '' && (
                            str_contains($msg, 'No query results')
                            || str_contains($msg, 'could not be found')
                        );
                    @endphp
                    @if ($msg !== '' && ! $hideRaw)
                        {{ $msg }}
                    @else
                        Verifique o endereço ou volte ao início. Se você estava numa loja, confira o link (nome da loja na URL).
                    @endif
                </p>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <a href="{{ url('/') }}" class="btn btn-primary">Ir ao site</a>
                    <button type="button" class="btn btn-outline-secondary" onclick="history.back()">Voltar</button>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
