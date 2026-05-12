<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('empresa_loja_banner_imagens')) {
            return;
        }

        Schema::create('empresa_loja_banner_imagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('caminho', 512);
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();

            $table->index(['empresa_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_loja_banner_imagens');
    }
};
