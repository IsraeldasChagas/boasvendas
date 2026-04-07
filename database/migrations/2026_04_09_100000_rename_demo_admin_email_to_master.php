<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bases antigas tinham admin@vendaffacil.com.br; passamos a master@vendaffacil.com.br (nome "Master").
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('users')->where('email', 'master@vendaffacil.com.br')->exists()) {
            return;
        }

        $legacy = DB::table('users')->where('email', 'admin@vendaffacil.com.br')->first();
        if (! $legacy) {
            return;
        }

        DB::table('users')
            ->where('id', $legacy->id)
            ->update([
                'email' => 'master@vendaffacil.com.br',
                'name' => 'Master',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        //
    }
};
