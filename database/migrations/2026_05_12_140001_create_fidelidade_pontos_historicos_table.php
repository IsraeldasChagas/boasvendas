<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fidelidade_pontos_historicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('cartao_fidelidade_id')->constrained('fidelidade_cartoes')->cascadeOnDelete();
            $table->string('tipo_movimento', 32);
            $table->integer('pontos');
            $table->string('descricao', 500)->nullable();
            $table->timestamps();

            // Nome curto: MySQL limita identificadores a 64 caracteres (o padrão do Laravel estourava).
            $table->index(['empresa_id', 'cartao_fidelidade_id'], 'fd_pts_hist_emp_cart_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fidelidade_pontos_historicos');
    }
};
