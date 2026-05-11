<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnpresentedGuide extends Model
{
    protected $fillable = [
        'guide', 
        'patient_name', 
        'professional_name', 
        'procedure', 
        'request_date',
        'patient_id',
        'professional_id',
        'therapy_id'
    ];

    // --- RELACIONAMENTOS ---

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
}