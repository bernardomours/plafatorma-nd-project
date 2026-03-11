<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $patients = DB::table('patients')
            ->whereNotNull('coordinator_id')
            ->orWhereNotNull('supervisor_id')
            ->get();

        foreach ($patients as $patient) {
            DB::table('patient_services')->insert([
                'patient_id' => $patient->id,
                'service_type_id' => 1,                
                'coordinator_id' => $patient->coordinator_id,
                'supervisor_id' => $patient->supervisor_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('patient_services')->truncate();
    }
};