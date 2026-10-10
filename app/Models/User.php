<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the practitioner record for this user.
     * A User can be a Practitioner (doctor, nurse, etc.)
     */
    public function practitioner(): HasOne
    {
        return $this->hasOne(Practitioner::class);
    }

    /**
     * Get encounters where this user is the practitioner.
     */
    public function encounters(): HasMany
    {
        return $this->hasMany(Encounter::class, 'practitioner_id');
    }

    /**
     * Get observations recorded by this user.
     */
    public function recordedObservations(): HasMany
    {
        return $this->hasMany(Observation::class, 'recorder_id');
    }

    /**
     * Get conditions recorded by this user.
     */
    public function recordedConditions(): HasMany
    {
        return $this->hasMany(Condition::class, 'recorder_id');
    }

    /**
     * Get allergy intolerances recorded by this user.
     */
    public function recordedAllergyIntolerances(): HasMany
    {
        return $this->hasMany(AllergyIntolerance::class, 'recorder_id');
    }

    /**
     * Check if user can access FHIR resource.
     *
     * @param  string  $resourceType  FHIR resource type (Patient, Encounter, Observation, etc.)
     * @param  string  $action  Action (view, create, update, delete)
     */
    public function canAccessFhirResource(string $resourceType, string $action): bool
    {
        // Allow all authenticated users for now
        return true;
    }
}
