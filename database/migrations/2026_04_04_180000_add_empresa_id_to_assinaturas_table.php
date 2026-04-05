<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assinaturas', function (Blueprint $table) {
            $table->foreignId('empresa_id')->nullable()->after('id')->constrained('empresas')->nullOnDelete();
        });

        foreach (DB::table('assinaturas')->get() as $row) {
            $eid = DB::table('empresas')->where('nome', $row->empresa_nome)->value('id');
            if ($eid) {
                DB::table('assinaturas')->where('id', $row->id)->update(['empresa_id' => $eid]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('assinaturas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('empresa_id');
        });
    }
};
