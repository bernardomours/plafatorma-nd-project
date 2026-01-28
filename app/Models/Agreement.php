<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
}
