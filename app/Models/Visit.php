<?php

namespace App\Models;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Visit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'patient_id',
        'professional_id',
        'happened_at',
        'type',
        'service_type_id',
        'status',
        'notes',
        'therapy_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'happened_at' => 'datetime',
        'type' => VisitType::class,
        'status' => VisitStatus::class,
    ];

    /**
     * Get the patient that owns the visit.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }
    
    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }
    
    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class)->withTrashed();
    }

    public function therapy()
    {
        return $this->belongsTo(Therapy::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['patient_id', 'professional_id', 'type', 'status', 'happened_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }
}
