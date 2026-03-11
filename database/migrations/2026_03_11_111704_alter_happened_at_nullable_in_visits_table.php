<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            // O comando ->change() avisa o banco que estamos apenas alterando a coluna!
            // Nota: Se a sua coluna original for do tipo timestamp() em vez de dateTime(), mude aqui embaixo.
            $table->date('happened_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            // Reverte a alteração caso você precise desfazer no futuro
            $table->dateTime('happened_at')->nullable(false)->change();
        });
    }
};
