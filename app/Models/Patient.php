<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Scopes\UnitScope;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Patient extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected static $recordEvents = ['created', 'updated', 'restored'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'birth_date',
        'cpf',
        'guardian_name',
        'guardian_phone',
        'unit_id',
        'agreement_id',
        'is_active',
        'agreement_number',
        'supervisor_id',
        'coordinator_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'birth_date' => 'date',
            'unit_id' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'unit_id', 'agreement_id', 'status', 'birth_date', 'guardian_name', 'guardian_phone']) 
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    public function patientServices()
    {
        return $this->hasMany(PatientService::class);
    }

    public function movementHistories()
    {
        return $this->morphMany(MovementHistory::class, 'moveable');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'supervisor_id');
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'coordinator_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function requestedServices(): HasMany
    {
        return $this->hasMany(RequestedService::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}
