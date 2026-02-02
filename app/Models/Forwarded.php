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
        'forwarding_date',
        'city',
        'agreement_id',
    ];

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }
}
