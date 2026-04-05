<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fidelidade_cartoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('telefone_normalizado', 20);
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->unsignedInteger('selos')->default(0);
            $table->unsignedInteger('total_resgates')->default(0);
            $table->timestamps();

            $table->unique(['empresa_id', 'telefone_normalizado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fidelidade_cartoes');
    }
};
