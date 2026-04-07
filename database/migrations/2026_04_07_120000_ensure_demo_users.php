<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Garante utilizadores de demonstração na base (como DatabaseSeeder), sem depender de db:seed.
 * Senha: password — altere em produção.
 *
 * Master: master@vendaffacil.com.br (nome "Master") — deve constar em VENDAFFACIL_ADMIN_EMAILS no .env para /admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $passwordHash = Hash::make('password');

        if (! DB::table('users')->where('email', 'master@vendaffacil.com.br')->exists()) {
            DB::table('users')->insert([
                'empresa_id' => null,
                'role' => 'operador',
                'name' => 'Master',
                'email' => 'master@vendaffacil.com.br',
                'email_verified_at' => null,
                'password' => $passwordHash,
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $empresaId = DB::table('empresas')->where('slug', 'demo')->value('id')
            ?? DB::table('empresas')->where('nome', 'Lanchonete Demo')->value('id');

        if (! $empresaId) {
            return;
        }

        $empresaUser = DB::table('users')->where('email', 'empresa@vendaffacil.com.br')->first();

        if (! $empresaUser) {
            DB::table('users')->insert([
                'empresa_id' => $empresaId,
                'role' => 'gestor',
                'name' => 'Empresa Demo',
                'email' => 'empresa@vendaffacil.com.br',
                'email_verified_at' => null,
                'password' => $passwordHash,
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return;
        }

        if ($empresaUser->empresa_id === null || (int) $empresaUser->empresa_id !== (int) $empresaId) {
            DB::table('users')
                ->where('id', $empresaUser->id)
                ->update([
                    'empresa_id' => $empresaId,
                    'role' => 'gestor',
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        // Não remove utilizadores em produção.
    }
};
