<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Professional extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'cpf',
        'phone',
        'birth_date',
        'register_number',
        'therapy_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'birth_date' => 'date',
            'therapy_id' => 'integer',
        ];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

        public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
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
