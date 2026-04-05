<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('email_contato')->nullable();
            $table->string('cnpj', 32)->nullable();
            $table->foreignId('plano_id')->nullable()->constrained('planos')->nullOnDelete();
            $table->string('status', 32);
            $table->string('modulos_resumo')->nullable();
            $table->date('cliente_desde')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
