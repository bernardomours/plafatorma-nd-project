<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            // Primeiro removemos a chave estrangeira
            $table->dropForeign(['unit_id']); 
            // Depois removemos a coluna
            $table->dropColumn('unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            // Caso precise reverter, a coluna volta a existir
            $table->foreignId('unit_id')->nullable()->constrained('units');
        });
    }
};