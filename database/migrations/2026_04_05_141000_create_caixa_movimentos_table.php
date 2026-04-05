<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caixa_movimentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caixa_turno_id')->constrained('caixa_turnos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 32);
            $table->string('descricao', 500);
            $table->decimal('valor', 12, 2);
            $table->timestamps();

            $table->index('caixa_turno_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caixa_movimentos');
    }
};
