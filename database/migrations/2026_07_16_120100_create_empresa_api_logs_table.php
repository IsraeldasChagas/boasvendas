<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa_api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->foreignId('empresa_api_token_id')->nullable()->constrained('empresa_api_tokens')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('method', 16);
            $table->string('endpoint', 500);
            $table->string('ip', 45)->nullable();
            $table->unsignedInteger('status_http')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('error')->nullable();
            $table->string('idempotency_key', 128)->nullable()->index();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['empresa_id', 'created_at']);
            $table->index(['empresa_id', 'status_http']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_api_logs');
    }
};
