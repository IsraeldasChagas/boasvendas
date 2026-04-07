<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ve_remessas', function (Blueprint $table) {
            if (! Schema::hasColumn('ve_remessas', 'produto_id')) {
                $table->foreignId('produto_id')->nullable()->after('ve_ponto_id')->constrained('produtos')->nullOnDelete();
                $table->index(['empresa_id', 'produto_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('ve_remessas', function (Blueprint $table) {
            if (Schema::hasColumn('ve_remessas', 'produto_id')) {
                $table->dropConstrainedForeignId('produto_id');
            }
        });
    }
};
