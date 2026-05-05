<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NeuroSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'neuro_assessment_id',
        'professional_id',
        'session_number',
        'date',
        'observations',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(NeuroAssessment::class, 'neuro_assessment_id');
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }
}