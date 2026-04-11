<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financeiro_despesas_fixas', function (Blueprint $table) {
            $table->date('vencimento')->nullable()->after('valor_mensal');
        });
    }

    public function down(): void
    {
        Schema::table('financeiro_despesas_fixas', function (Blueprint $table) {
            $table->dropColumn('vencimento');
        });
    }
};
