<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nome', 120);
            $table->string('token_hash', 64)->unique();
            $table->string('token_prefix', 16)->nullable()->index();
            $table->json('abilities')->nullable();
            $table->string('environment', 32)->default('homologacao');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->json('allowed_ips')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['empresa_id', 'revoked_at']);
            $table->index(['empresa_id', 'environment']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_api_tokens');
    }
};
