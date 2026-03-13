<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MovementHistory extends Model
{
    use LogsActivity;

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->dontSubmitEmptyLogs();
    }
}