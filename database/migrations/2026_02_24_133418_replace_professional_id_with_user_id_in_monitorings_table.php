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
        Schema::table('monitorings', function (Blueprint $table) {
            // Drop the old foreign key and column
            $table->dropForeign(['professional_id']);
            $table->dropColumn('professional_id');

            // Add the new user_id column
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitorings', function (Blueprint $table) {
            // Drop the new user_id column
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            // Re-add the old professional_id column
            $table->foreignId('professional_id')->nullable()->constrained()->onDelete('set null');
        });
    }
};
