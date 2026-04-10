<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ve_remessas', function (Blueprint $table) {
            $table->unsignedInteger('quantidade')->default(1)->after('produto_id');
        });
    }

    public function down(): void
    {
        Schema::table('ve_remessas', function (Blueprint $table) {
            $table->dropColumn('quantidade');
        });
    }
};
