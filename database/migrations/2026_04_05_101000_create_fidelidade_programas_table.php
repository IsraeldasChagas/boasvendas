<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fidelidade_programas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->unique()->constrained('empresas')->cascadeOnDelete();
            $table->boolean('ativo')->default(false);
            $table->string('nome_exibicao', 120)->default('Cartão fidelidade');
            $table->unsignedSmallInteger('pedidos_meta')->default(10);
            $table->string('tipo_recompensa', 32);
            $table->foreignId('produto_id')->nullable()->constrained('produtos')->nullOnDelete();
            $table->decimal('valor_desconto', 10, 2)->nullable();
            $table->string('texto_recompensa', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fidelidade_programas');
    }
};
