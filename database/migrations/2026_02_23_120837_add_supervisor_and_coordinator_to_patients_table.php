<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->foreignId('supervisor_id')->nullable()->constrained('professionals')->onDelete('set null');
            $table->foreignId('coordinator_id')->nullable()->constrained('professionals')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropForeign(['supervisor_id']);
            $table->dropForeign(['coordinator_id']);
            $table->dropColumn(['supervisor_id', 'coordinator_id']);
        });
    }
};