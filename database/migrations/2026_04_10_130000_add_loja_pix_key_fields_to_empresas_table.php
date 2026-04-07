<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('loja_pix_chave_tipo', 20)->nullable()->after('loja_pix_instrucoes');
            $table->string('loja_pix_chave_valor', 255)->nullable()->after('loja_pix_chave_tipo');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['loja_pix_chave_tipo', 'loja_pix_chave_valor']);
        });
    }
};
