<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fidelidade_cartoes', function (Blueprint $table) {
            $table->string('codigo_fidelidade', 40)->nullable()->after('cliente_id');
            $table->string('status', 20)->default('ativo')->after('total_resgates');
            $table->unsignedInteger('pontos')->default(0)->after('status');
        });

        Schema::table('fidelidade_cartoes', function (Blueprint $table) {
            $table->unique('codigo_fidelidade');
        });

        if (Schema::hasColumn('fidelidade_cartoes', 'pontos') && Schema::hasColumn('fidelidade_cartoes', 'selos')) {
            DB::table('fidelidade_cartoes')->update(['pontos' => DB::raw('selos')]);
        }
    }

    public function down(): void
    {
        Schema::table('fidelidade_cartoes', function (Blueprint $table) {
            $table->dropUnique(['codigo_fidelidade']);
            $table->dropColumn(['codigo_fidelidade', 'status', 'pontos']);
        });
    }
};
