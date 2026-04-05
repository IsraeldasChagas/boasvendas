<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ve_remessas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('ve_ponto_id')->nullable()->constrained('ve_pontos')->nullOnDelete();
            $table->string('titulo')->nullable();
            $table->string('status', 24)->default('preparacao');
            $table->timestamps();

            $table->index(['empresa_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ve_remessas');
    }
};
