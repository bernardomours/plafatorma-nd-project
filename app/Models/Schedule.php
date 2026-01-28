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
        'type_therapy',
    ];

    /**
     * The attributes that should be cast.
     * We are removing all casting for time fields to handle it manually.
     *
     * @var array
     */
    protected $casts = [];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
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
