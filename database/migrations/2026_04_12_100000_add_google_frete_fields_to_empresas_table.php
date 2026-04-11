<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->decimal('loja_frete_google_rs_por_km', 10, 2)->nullable()->after('loja_frete_modo');
            $table->decimal('loja_frete_google_taxa_minima', 10, 2)->nullable()->after('loja_frete_google_rs_por_km');
            $table->decimal('loja_frete_google_km_max', 8, 2)->nullable()->after('loja_frete_google_taxa_minima');
            $table->string('loja_frete_origem_endereco', 500)->nullable()->after('loja_frete_google_km_max');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'loja_frete_google_rs_por_km',
                'loja_frete_google_taxa_minima',
                'loja_frete_google_km_max',
                'loja_frete_origem_endereco',
            ]);
        });
    }
};
