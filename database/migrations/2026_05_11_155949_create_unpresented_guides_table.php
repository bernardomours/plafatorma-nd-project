<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unpresented_guides', function (Blueprint $table) {
            $table->id();         
            $table->string('guide')->unique();             
            $table->string('patient_name')->nullable();
            $table->string('professional_name')->nullable();
            $table->string('procedure')->nullable();
            $table->date('request_date')->nullable();           
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('professional_id')->nullable()->constrained('professionals')->nullOnDelete();
            $table->foreignId('therapy_id')->nullable()->constrained('therapies')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unpresented_guides');
    }
};