<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('administrative')->after('email');
            $table->softDeletes(); 
        });

        DB::table('users')->where('is_admin', 1)->update(['role' => 'admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(0);
        });

        DB::table('users')->where('role', 'admin')->update(['is_admin' => 1]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
            $table->dropSoftDeletes(); 
        });
    }
};