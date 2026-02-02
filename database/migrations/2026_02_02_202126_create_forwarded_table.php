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
        Schema::create('forwarded', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('forwarding_date');
            $table->string('city');
            $table->string('status');
            $table->foreignId('agreement_id')->nullable()->constrained('agreements')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forwarded');
    }
};
