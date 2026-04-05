<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('codigo_publico', 32)->unique();
            $table->string('canal', 32)->default('loja');
            $table->string('cliente_nome', 120);
            $table->string('cliente_telefone', 32);
            $table->string('cliente_email')->nullable();
            $table->string('endereco', 255);
            $table->string('complemento', 120)->nullable();
            $table->string('forma_pagamento', 32);
            $table->text('observacoes')->nullable();
            $table->string('status', 32);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('taxa_entrega', 12, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();

            $table->index(['empresa_id', 'status']);
            $table->index(['empresa_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
