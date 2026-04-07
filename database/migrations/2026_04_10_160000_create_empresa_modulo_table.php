<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa_modulo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('modulo_id')->constrained('modulos')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['empresa_id', 'modulo_id']);
            $table->index(['modulo_id', 'empresa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_modulo');
    }
};
