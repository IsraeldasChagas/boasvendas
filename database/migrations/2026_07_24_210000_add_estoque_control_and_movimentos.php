<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->boolean('controla_estoque')->default(true)->after('estoque');
        });

        // Movimentos de produto acabado (inteiro) e de insumo (fracionado: g, ml, un).
        Schema::create('estoque_movimentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('produto_id')->nullable()->constrained('produtos')->cascadeOnDelete();
            $table->unsignedBigInteger('insumo_id')->nullable();
            $table->string('tipo', 32);
            $table->decimal('delta', 14, 3);
            $table->decimal('saldo_apos', 14, 3);
            $table->string('unidade', 8)->nullable();
            $table->nullableMorphs('referencia');
            $table->string('observacao', 500)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['empresa_id', 'created_at']);
            $table->index(['produto_id', 'created_at']);
            $table->index(['insumo_id', 'created_at']);
            $table->index(['tipo', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estoque_movimentos');

        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn('controla_estoque');
        });
    }
};
