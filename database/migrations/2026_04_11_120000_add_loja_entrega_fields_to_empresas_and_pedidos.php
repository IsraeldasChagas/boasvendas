<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->decimal('loja_taxa_entrega_padrao', 12, 2)->nullable()->after('whatsapp');
            $table->boolean('loja_permite_retirada_balcao')->default(true)->after('loja_taxa_entrega_padrao');
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->string('tipo_entrega', 32)->default('entrega')->after('canal');
            $table->char('cep_entrega', 8)->nullable()->after('complemento');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['tipo_entrega', 'cep_entrega']);
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['loja_taxa_entrega_padrao', 'loja_permite_retirada_balcao']);
        });
    }
};
