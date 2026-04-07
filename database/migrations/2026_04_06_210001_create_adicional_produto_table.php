<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adicional_produto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
            $table->foreignId('adicional_id')->constrained('adicionais')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['produto_id', 'adicional_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adicional_produto');
    }
};
