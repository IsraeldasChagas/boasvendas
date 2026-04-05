<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ve_venda_externa_registros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('ve_ponto_id')->constrained('ve_pontos')->cascadeOnDelete();
            $table->date('data_venda');
            $table->decimal('valor', 12, 2);
            $table->string('referencia', 120)->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'data_venda']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ve_venda_externa_registros');
    }
};
