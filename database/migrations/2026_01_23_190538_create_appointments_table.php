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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->date('appointment_date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->foreignId('service_type_id');
            $table->integer('session_number')->nullable();
            $table->foreignId('patient_id');
            $table->foreignId('professional_id');
            $table->foreignId('therapy_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
