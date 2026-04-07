<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('vendaffacil:link-demo-user', function (): int {
    $empresaId = DB::table('empresas')->where('slug', 'demo')->value('id')
        ?? DB::table('empresas')->where('nome', 'Lanchonete Demo')->value('id');

    if (! $empresaId) {
        $this->error('Empresa demo não encontrada (slug "demo" ou nome "Lanchonete Demo").');

        return 1;
    }

    $user = DB::table('users')->where('email', 'empresa@vendaffacil.com.br')->first();

    if (! $user) {
        $this->error('Utilizador empresa@vendaffacil.com.br não existe. Corra: php artisan migrate --force');

        return 1;
    }

    DB::table('users')
        ->where('id', $user->id)
        ->update([
            'empresa_id' => $empresaId,
            'role' => 'gestor',
            'updated_at' => now(),
        ]);

    $this->info('OK: empresa@vendaffacil.com.br vinculado à empresa demo (id '.$empresaId.').');

    return 0;
})->purpose('Vincula o utilizador demo da empresa à loja /loja/demo (corrige login no painel)');
