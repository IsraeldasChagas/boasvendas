<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ve_acertos', function (Blueprint $table) {
            if (! Schema::hasColumn('ve_acertos', 'valor_repasse_unitario')) {
                $table->decimal('valor_repasse_unitario', 12, 2)->nullable()->after('valor_vendas');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ve_acertos', function (Blueprint $table) {
            if (Schema::hasColumn('ve_acertos', 'valor_repasse_unitario')) {
                $table->dropColumn('valor_repasse_unitario');
            }
        });
    }
};
