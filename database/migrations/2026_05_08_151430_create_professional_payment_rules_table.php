<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_payment_rules', function (Blueprint $table) {
            $table->id();            
            $table->foreignId('professional_id')->constrained()->cascadeOnDelete();        
            $table->foreignId('therapy_id')->nullable()->constrained()->cascadeOnDelete();           
            $table->foreignId('agreement_id')->nullable()->constrained()->cascadeOnDelete();           
            $table->enum('payment_type', ['por_sessao', 'por_hora', 'por_dia']);            
            $table->decimal('amount', 10, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_payment_rules');
    }
};