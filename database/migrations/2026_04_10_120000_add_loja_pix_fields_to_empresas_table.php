<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->text('loja_pix_instrucoes')->nullable()->after('slug');
            $table->text('loja_pix_copia_cola')->nullable()->after('loja_pix_instrucoes');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['loja_pix_instrucoes', 'loja_pix_copia_cola']);
        });
    }
};
