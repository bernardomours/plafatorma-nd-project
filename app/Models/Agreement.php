<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agreement extends Model
{
    protected $fillable = ['name', 'is_active'];

    protected static function booted(): void
    {
        parent::booted();
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class);
    }

    public function forwardings(): HasMany
    {
        return $this->hasMany(Forwarded::class);
    }
}
