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
        Schema::table('visits', function (Blueprint $table) {
            $table->date('happened_at')->nullable()->change();
        });
        
        DB::statement("UPDATE visits SET happened_at = NULL WHERE happened_at < '2000-01-01'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nullable_visits', function (Blueprint $table) {
            $table->date('happened_at')->nullable(false)->change();
        });
    }
};
