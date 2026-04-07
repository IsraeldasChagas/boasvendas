<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ve_acertos', function (Blueprint $table) {
            if (! Schema::hasColumn('ve_acertos', 'quantidade')) {
                $table->decimal('quantidade', 12, 3)->nullable()->after('valor_repasse_unitario');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ve_acertos', function (Blueprint $table) {
            if (Schema::hasColumn('ve_acertos', 'quantidade')) {
                $table->dropColumn('quantidade');
            }
        });
    }
};
