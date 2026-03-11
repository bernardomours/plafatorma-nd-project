<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_type_id')->constrained('service_types');
            $table->foreignId('coordinator_id')->nullable()->constrained('professionals');
            $table->foreignId('supervisor_id')->nullable()->constrained('professionals');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_services');
    }
};