<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('loja_filial_nome', 120)->nullable()->after('logo');
            $table->string('loja_filial_logo', 255)->nullable()->after('loja_filial_nome');
            $table->string('loja_filial_link_url', 500)->nullable()->after('loja_filial_logo');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['loja_filial_nome', 'loja_filial_logo', 'loja_filial_link_url']);
        });
    }
};
