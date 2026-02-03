<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Forwarded extends Model
{
    use HasFactory;

    protected $table = 'forwarded';

    protected $fillable = [
        'name',
        'status',
        'status_return',
        'forwarding_date',
        'unit_id',
        'agreement_id',
    ];

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
