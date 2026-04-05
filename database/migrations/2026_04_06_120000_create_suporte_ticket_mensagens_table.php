<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suporte_ticket_mensagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('suporte_ticket_id')->constrained('suporte_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('corpo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suporte_ticket_mensagens');
    }
};
