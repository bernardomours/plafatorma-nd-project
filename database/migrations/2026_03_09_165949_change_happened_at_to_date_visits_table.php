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
        DB::statement("SET SESSION sql_mode = ''");
        Schema::table('visits', function (Blueprint $table) {
            $table->date('happened_at')->change();
        });
    }

    public function down(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        Schema::table('visits', function (Blueprint $table) {
            $table->dateTime('happened_at')->change();
        });
    }
};
