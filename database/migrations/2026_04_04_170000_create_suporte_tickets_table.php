<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suporte_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->string('assunto');
            $table->text('descricao')->nullable();
            $table->string('prioridade', 32);
            $table->string('status', 32);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suporte_tickets');
    }
};
