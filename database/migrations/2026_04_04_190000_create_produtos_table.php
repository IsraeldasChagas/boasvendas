<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('sku', 64);
            $table->string('nome');
            $table->string('categoria')->nullable();
            $table->decimal('preco', 10, 2);
            $table->unsignedInteger('estoque')->default(0);
            $table->text('descricao')->nullable();
            $table->boolean('visivel_loja')->default(true);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
