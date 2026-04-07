<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige utilizador empresa@vendaffacil.com.br sem empresa_id (login falha com middleware empresa.painel).
 * Idempotente. Empresa demo: slug "demo" ou nome "Lanchonete Demo".
 */
return new class extends Migration
{
    public function up(): void
    {
        $empresaId = DB::table('empresas')->where('slug', 'demo')->value('id')
            ?? DB::table('empresas')->where('nome', 'Lanchonete Demo')->value('id');

        if (! $empresaId) {
            return;
        }

        $now = now();

        DB::table('users')
            ->where('email', 'empresa@vendaffacil.com.br')
            ->where(function ($q) use ($empresaId) {
                $q->whereNull('empresa_id')
                    ->orWhere('empresa_id', '!=', $empresaId);
            })
            ->update([
                'empresa_id' => $empresaId,
                'role' => 'gestor',
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        //
    }
};
