<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produto_ingredientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
            $table->string('nome', 120);
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();
        });

        Schema::table('produtos', function (Blueprint $table) {
            $table->unsignedTinyInteger('max_ingredientes_retirar')->nullable()->after('permite_adicionais');
        });
    }

    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn('max_ingredientes_retirar');
        });

        Schema::dropIfExists('produto_ingredientes');
    }
};
