<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'day_of_week',
        'start_time',
        'end_time',
        'patient_id',
        'professional_id',
        'therapy_id',
        'service_type_id',
    ];

    /**
     * Get the patient that owns the schedule.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the professional that owns the schedule.
     */
    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    /**
     * Get the therapy that owns the schedule.
     */
    public function therapy(): BelongsTo
    {
        return $this->belongsTo(Therapy::class);
    }

    /**
     * Get the service type that owns the schedule.
     */
    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }
}
