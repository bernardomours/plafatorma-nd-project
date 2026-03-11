<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientService extends Model
{
    protected $fillable = [
        'patient_id',
        'service_type_id',
        'coordinator_id',
        'supervisor_id',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'coordinator_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'supervisor_id');
    }
}