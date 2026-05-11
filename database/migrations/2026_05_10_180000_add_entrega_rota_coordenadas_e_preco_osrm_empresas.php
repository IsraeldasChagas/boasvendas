<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            if (! Schema::hasColumn('empresas', 'loja_entrega_lat_origem')) {
                $table->decimal('loja_entrega_lat_origem', 10, 7)->nullable()->after('loja_frete_origem_endereco');
            }
            if (! Schema::hasColumn('empresas', 'loja_entrega_lng_origem')) {
                $table->decimal('loja_entrega_lng_origem', 10, 7)->nullable()->after('loja_entrega_lat_origem');
            }
            if (! Schema::hasColumn('empresas', 'loja_entrega_km_incluso')) {
                $table->decimal('loja_entrega_km_incluso', 8, 2)->default(3)->after('loja_entrega_lng_origem');
            }
            if (! Schema::hasColumn('empresas', 'loja_entrega_valor_km_extra')) {
                $table->decimal('loja_entrega_valor_km_extra', 10, 2)->default(2)->after('loja_entrega_km_incluso');
            }
            if (! Schema::hasColumn('empresas', 'loja_entrega_gratis_acima_pedido')) {
                $table->decimal('loja_entrega_gratis_acima_pedido', 12, 2)->nullable()->after('loja_entrega_valor_km_extra');
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $cols = [
                'loja_entrega_lat_origem',
                'loja_entrega_lng_origem',
                'loja_entrega_km_incluso',
                'loja_entrega_valor_km_extra',
                'loja_entrega_gratis_acima_pedido',
            ];
            foreach ($cols as $c) {
                if (Schema::hasColumn('empresas', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
