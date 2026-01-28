<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RequestedService extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_type',
        'month_year',
        'requisition_number',
        'requested_hours',
        'approved_hours',
        'planned_hours',
        'patient_id',
        'therapy_id',
    ];

    protected $casts = [
        'month_year' => 'string',
        'requested_hours' => 'decimal:2',
        'approved_hours' => 'decimal:2',
        'planned_hours' => 'decimal:2',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function therapy(): BelongsTo
    {
        return $this->belongsTo(Therapy::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\UnitScope);
    }
}