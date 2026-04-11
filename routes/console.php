<?php

use App\Models\Produto;
use App\Support\GoogleMapsDistanceMatrix;
use Database\Seeders\RestaurarEmpresaDemoSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('vendaffacil:google-maps-test', function (): int {
    $key = config('services.google_maps.api_key');
    if (! filled($key)) {
        $this->error('Defina GOOGLE_MAPS_API_KEY no .env e rode: php artisan config:clear');

        return 1;
    }

    $this->info('Consultando Distance Matrix (São Paulo → São Paulo, rota de carro)…');

    $km = GoogleMapsDistanceMatrix::distanciaKmRodoviaria(
        'Praça da Sé, São Paulo - SP, Brasil',
        'Av. Paulista, 1578 - São Paulo - SP, Brasil',
        is_string($key) ? $key : null
    );

    if ($km === null) {
        $this->error('A API não devolveu distância. Verifique se a Distance Matrix API está ativa e se a chave tem permissão.');

        return 1;
    }

    $this->info('OK: distância retornada ≈ '.number_format($km, 2, ',', '.').' km. Frete Google pode calcular no site.');

    return 0;
})->purpose('Testa GOOGLE_MAPS_API_KEY e a Distance Matrix API (diagnóstico rápido)');

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

Artisan::command('vendaffacil:converter-fotos-produto-para-jpg {--dry-run : Não grava no disco nem altera no banco}', function (): int {
    $dry = (bool) $this->option('dry-run');
    $total = 0;
    $convertidos = 0;
    $pulados = 0;
    $falhas = 0;

    Produto::query()
        ->whereNotNull('foto')
        ->where('foto', '!=', '')
        ->orderBy('id')
        ->chunkById(200, function ($rows) use ($dry, &$total, &$convertidos, &$pulados, &$falhas) {
            foreach ($rows as $p) {
                $total++;
                $full = $p->resolveFotoAbsolutePath();
                if ($full === null || ! is_file($full)) {
                    $pulados++;

                    continue;
                }

                $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
                if (! in_array($ext, ['webp', 'avif'], true)) {
                    $pulados++;

                    continue;
                }

                $img = null;
                try {
                    if ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
                        $img = @imagecreatefromwebp($full);
                    } elseif ($ext === 'avif' && function_exists('imagecreatefromavif')) {
                        $img = @imagecreatefromavif($full);
                    } else {
                        $raw = @file_get_contents($full);
                        if (is_string($raw) && $raw !== '') {
                            $img = @imagecreatefromstring($raw);
                        }
                    }

                    if ($img === null || $img === false) {
                        $falhas++;
                        $this->warn("Falhou ao decodificar: produto {$p->id} ({$p->foto})");

                        continue;
                    }

                    $w = imagesx($img);
                    $h = imagesy($img);
                    $dst = imagecreatetruecolor($w, $h);
                    $white = imagecolorallocate($dst, 255, 255, 255);
                    imagefilledrectangle($dst, 0, 0, $w, $h, $white);
                    imagecopy($dst, $img, 0, 0, 0, 0, $w, $h);

                    ob_start();
                    imagejpeg($dst, null, 85);
                    $jpeg = ob_get_clean();

                    imagedestroy($dst);
                    imagedestroy($img);

                    if (! is_string($jpeg) || $jpeg === '') {
                        $falhas++;
                        $this->warn("Falhou ao gerar JPEG: produto {$p->id} ({$p->foto})");

                        continue;
                    }

                    $dir = 'produtos/'.$p->empresa_id;
                    $nome = Str::uuid()->toString().'.jpg';
                    $path = $dir.'/'.$nome;

                    if (! $dry) {
                        Storage::disk('uploads')->put($path, $jpeg);
                        $p->update(['foto' => $path]);
                    }

                    $convertidos++;
                    $this->line("OK: produto {$p->id} -> {$path}".($dry ? ' (dry-run)' : ''));
                } catch (Throwable $e) {
                    $falhas++;
                    $this->warn("Erro: produto {$p->id} ({$p->foto})");
                }
            }
        });

    $this->info("Total: {$total} | Convertidos: {$convertidos} | Pulados: {$pulados} | Falhas: {$falhas}".($dry ? ' (dry-run)' : ''));

    return $falhas > 0 ? 1 : 0;
})->purpose('Converte fotos antigas (WebP/AVIF) para JPG em public/uploads e atualiza a coluna foto');
