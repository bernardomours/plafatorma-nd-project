<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NeuroAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'professional_id',
        'status',
        'current_session',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(NeuroSession::class)->orderBy('session_number', 'asc');
    }
}