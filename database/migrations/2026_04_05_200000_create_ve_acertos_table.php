<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ve_acertos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('ve_ponto_id')->constrained('ve_pontos')->cascadeOnDelete();
            $table->foreignId('ve_remessa_id')->nullable()->constrained('ve_remessas')->nullOnDelete();
            $table->date('data_acerto')->nullable();
            $table->decimal('valor_vendas', 12, 2)->nullable();
            $table->decimal('valor_repasse', 12, 2)->nullable();
            $table->string('status', 16)->default('aberto');
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'status']);
            $table->index(['empresa_id', 'data_acerto']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ve_acertos');
    }
};
