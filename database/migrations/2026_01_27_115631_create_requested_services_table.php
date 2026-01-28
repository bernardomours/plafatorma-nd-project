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
        Schema::create('requested_services', function (Blueprint $table) {
            $table->id();
            $table->string('service_type');
            $table->date('month_year');
            $table->string('requisition_number')->nullable();
            $table->decimal('requested_hours', 8, 2);
            $table->decimal('approved_hours', 8, 2);
            $table->decimal('planned_hours', 8, 2);
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('therapy_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requested_services');
    }
};
