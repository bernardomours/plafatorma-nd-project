<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Cria a tabela intermediária
        Schema::create('professional_unit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
        });

        // 2. Copia os dados antigos (Para ninguém perder a unidade que já estava cadastrada!)
        $professionals = DB::table('professionals')->whereNotNull('unit_id')->get();
        foreach ($professionals as $prof) {
            DB::table('professional_unit')->insert([
                'professional_id' => $prof->id,
                'unit_id' => $prof->unit_id,
            ]);
        }

        // 3. Remove a coluna antiga (pois agora usamos a tabela nova)
        Schema::table('professionals', function (Blueprint $table) {
            $table->dropForeign(['unit_id']); // Remove a chave estrangeira se existir
            $table->dropColumn('unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('professionals', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->constrained();
        });
        Schema::dropIfExists('professional_unit');
    }
};