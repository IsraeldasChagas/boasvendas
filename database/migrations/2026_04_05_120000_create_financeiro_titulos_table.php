<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financeiro_titulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('tipo', 16);
            $table->string('contraparte', 255)->nullable();
            $table->string('descricao', 500);
            $table->decimal('valor', 12, 2);
            $table->date('vencimento');
            $table->string('status', 16)->default('aberto');
            $table->date('pago_em')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'tipo', 'status']);
            $table->index(['empresa_id', 'vencimento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financeiro_titulos');
    }
};
