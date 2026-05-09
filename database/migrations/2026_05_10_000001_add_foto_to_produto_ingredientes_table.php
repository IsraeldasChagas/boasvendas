<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produto_ingredientes', function (Blueprint $table) {
            $table->string('foto', 500)->nullable()->after('nome');
        });
    }

    public function down(): void
    {
        Schema::table('produto_ingredientes', function (Blueprint $table) {
            $table->dropColumn('foto');
        });
    }
};
