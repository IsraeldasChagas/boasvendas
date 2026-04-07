<?php

use App\Models\Produto;
use Database\Seeders\RestaurarEmpresaDemoSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('vendaffacil:restaurar-empresa-demo', function (): int {
    $this->info('A restaurar empresa demo (Lanchonete Demo / slug demo)…');

    Artisan::call('db:seed', ['--class' => RestaurarEmpresaDemoSeeder::class, '--force' => true]);
    $this->output->write(Artisan::output());

    if (! DB::table('users')->where('email', 'empresa@vendaffacil.com.br')->exists()) {
        $empresaId = DB::table('empresas')->where('slug', 'demo')->value('id')
            ?? DB::table('empresas')->where('nome', 'Lanchonete Demo')->value('id');
        if ($empresaId) {
            DB::table('users')->insert([
                'empresa_id' => $empresaId,
                'role' => 'gestor',
                'name' => 'Empresa Demo',
                'email' => 'empresa@vendaffacil.com.br',
                'email_verified_at' => null,
                'password' => Hash::make('password'),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->info('Utilizador empresa@vendaffacil.com.br criado (senha: password).');
        }
    }

    $code = Artisan::call('vendaffacil:link-demo-user');
    $this->output->write(Artisan::output());

    $this->info('Loja pública: /loja/demo — Login painel: empresa@vendaffacil.com.br (senha: password).');

    return $code;
})->purpose('Recria a empresa demo (Lanchonete Demo), dados de exemplo e vincula o utilizador empresa@vendaffacil.com.br');

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

Artisan::command('vendaffacil:copiar-fotos-storage-para-uploads', function (): int {
    $produtos = Produto::query()->whereNotNull('foto')->where('foto', '!=', '')->get();
    $copiados = 0;
    foreach ($produtos as $p) {
        $rel = ltrim(str_replace('\\', '/', (string) $p->foto), '/');
        $from = storage_path('app/public/'.$rel);
        $to = public_path('uploads/'.$rel);
        if (! is_file($from) || is_file($to)) {
            continue;
        }
        File::ensureDirectoryExists(dirname($to));
        if (@copy($from, $to)) {
            $copiados++;
            $this->line($rel);
        }
    }
    $this->info("Copiados: {$copiados} (demais já existiam em uploads ou não estão em storage).");

    return 0;
})->purpose('Copia fotos de produto de storage/app/public para public/uploads (imagens passam a abrir sem storage:link)');
