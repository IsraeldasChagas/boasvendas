<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adicionais', function (Blueprint $table) {
            $table->string('foto', 512)->nullable()->after('ordem');
        });
    }

    public function down(): void
    {
        Schema::table('adicionais', function (Blueprint $table) {
            $table->dropColumn('foto');
        });
    }
};
