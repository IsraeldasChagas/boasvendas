<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            if (! Schema::hasColumn('empresas', 'loja_entrega_chuva_ligado')) {
                $table->boolean('loja_entrega_chuva_ligado')->default(false);
            }
            if (! Schema::hasColumn('empresas', 'loja_entrega_chuva_percentual')) {
                $table->decimal('loja_entrega_chuva_percentual', 5, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            foreach (['loja_entrega_chuva_ligado', 'loja_entrega_chuva_percentual'] as $c) {
                if (Schema::hasColumn('empresas', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
