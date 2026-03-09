<?php

namespace App\Models;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'status',
        'notes',
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
    
    /**
     * Get the professional (supervisor/coordinator) that owns the visit.
     */
    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class)->withTrashed();
    }
}
