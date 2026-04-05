<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('produtos', 'categoria_id')) {
            return;
        }

        Schema::table('produtos', function (Blueprint $table) {
            $table->foreignId('categoria_id')->nullable()->after('empresa_id')->constrained('categorias')->nullOnDelete();
        });

        if (! Schema::hasColumn('produtos', 'categoria')) {
            return;
        }

        foreach (DB::table('produtos')->cursor() as $p) {
            $nomeCat = trim((string) ($p->categoria ?? ''));
            if ($nomeCat === '') {
                continue;
            }

            $cid = DB::table('categorias')
                ->where('empresa_id', $p->empresa_id)
                ->where('nome', $nomeCat)
                ->value('id');

            if (! $cid) {
                $cid = DB::table('categorias')->insertGetId([
                    'empresa_id' => $p->empresa_id,
                    'nome' => $nomeCat,
                    'ordem' => 0,
                    'ativo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('produtos')->where('id', $p->id)->update(['categoria_id' => $cid]);
        }

        if (Schema::hasColumn('produtos', 'categoria')) {
            Schema::table('produtos', function (Blueprint $table) {
                $table->dropColumn('categoria');
            });
        }
    }

    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->string('categoria')->nullable()->after('sku');
        });

        foreach (DB::table('produtos')->whereNotNull('categoria_id')->get() as $p) {
            $nome = DB::table('categorias')->where('id', $p->categoria_id)->value('nome');
            DB::table('produtos')->where('id', $p->id)->update(['categoria' => $nome]);
        }

        Schema::table('produtos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('categoria_id');
        });
    }
};
