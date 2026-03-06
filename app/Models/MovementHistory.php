<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovementHistory extends Model
{
    protected $fillable = [
        'action',
        'reason',
        'date',
        'user_id',
    ];

    // Diz que esse histórico pertence a alguém (Paciente ou Profissional)
    public function moveable(): MorphTo
    {
        return $this->morphTo();
    }

    // Diz quem foi o funcionário/admin que registrou isso no sistema
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}