<?php

namespace App\Models;

use App\Enums\ProfessionalRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Professional extends Model
{
    use HasFactory;
    use SoftDeletes;
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
        'email',
        'role',
        'deletion_reason',
        'user_id'
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
            'role' => ProfessionalRole::class,
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

    public function movementHistories()
    {
        return $this->morphMany(MovementHistory::class, 'moveable');
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\UnitScope);

        static::saved(function ($professional) {
            // 1. Verifica se é Coordenador ou Supervisor E se ele tem um email cadastrado
            if (in_array($professional->role, ['supervisor', 'coordinator']) && $professional->email) {
                
                // 2. Se ele ainda não tem um usuário vinculado, vamos criar!
                if (! $professional->user_id) {
                    
                    // Pega o CPF e remove os pontos e traços para virar a senha padrão
                    $cpfLimpo = preg_replace('/[^0-9]/', '', $professional->cpf);

                    $user = \App\Models\User::firstOrCreate(
                        ['email' => $professional->email], // Busca por esse email
                        [
                            'name' => $professional->name,
                            'password' => bcrypt($cpfLimpo), // A senha inicial é o CPF dele!
                        ]
                    );

                    // Conecta o ID do usuário novo ao nosso profissional silenciosamente
                    $professional->updateQuietly(['user_id' => $user->id]);
                } 
                // 3. Mas se ele JÁ TEM usuário vinculado, nós apenas sincronizamos os dados
                else {
                    $professional->user()->update([
                        'name' => $professional->name,
                        'email' => $professional->email,
                    ]);
                }
            }
        });
    }
}
