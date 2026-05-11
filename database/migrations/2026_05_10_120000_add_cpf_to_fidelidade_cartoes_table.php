<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fidelidade_cartoes', function (Blueprint $table) {
            $table->string('cpf_normalizado', 14)->nullable()->after('telefone_normalizado');
        });
    }

    public function down(): void
    {
        Schema::table('fidelidade_cartoes', function (Blueprint $table) {
            $table->dropColumn('cpf_normalizado');
        });
    }
};
