<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Scopes\UnitScope;

class Patient extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'birth_date',
        'cpf',
        'guardian_name',
        'guardian_phone',
        'unit_id',
        'agreement_id',
        'is_active'
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
            'unit_id' => 'integer',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\UnitScope());
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function requestedServices(): HasMany
    {
        return $this->hasMany(RequestedService::class);
    }
}
