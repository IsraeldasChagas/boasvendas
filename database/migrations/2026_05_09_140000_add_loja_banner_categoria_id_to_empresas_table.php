<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('empresas')) {
            return;
        }
        if (Schema::hasColumn('empresas', 'loja_banner_categoria_id')) {
            return;
        }
        Schema::table('empresas', function (Blueprint $table) {
            $table->foreignId('loja_banner_categoria_id')
                ->nullable()
                ->after('slug')
                ->constrained('categorias')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('empresas', 'loja_banner_categoria_id')) {
            return;
        }
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('loja_banner_categoria_id');
        });
    }
};
