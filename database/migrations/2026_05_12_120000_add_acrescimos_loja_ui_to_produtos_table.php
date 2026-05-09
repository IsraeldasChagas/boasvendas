<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('produtos')) {
            return;
        }
        if (Schema::hasColumn('produtos', 'acrescimos_loja_ui')) {
            return;
        }
        Schema::table('produtos', function (Blueprint $table) {
            $table->string('acrescimos_loja_ui', 16)->nullable()->default('stepper')->after('ingredientes_retirar_ui');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('produtos', 'acrescimos_loja_ui')) {
            Schema::table('produtos', function (Blueprint $table) {
                $table->dropColumn('acrescimos_loja_ui');
            });
        }
    }
};
