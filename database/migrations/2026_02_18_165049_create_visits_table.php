<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('professional_id')->constrained();
            $table->dateTime('happened_at'); 
            $table->enum('type', ['supervision', 'coordination'])
                  ->default('supervision')
                  ->index();
            $table->enum('status', ['pending', 'completed', 'cancelled'])
                  ->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};