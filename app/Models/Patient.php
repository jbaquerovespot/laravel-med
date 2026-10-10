<?php

namespace App\Models;

use App\Casts\EncryptedCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'document_number',
        'first_name',
        'last_name',
        'gender',
        'birth_date',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'marital_status',
        'emergency_contact_name',
        'emergency_contact_phone',
        'general_practitioner_id',
        'active',
        'biometric_enrolled',
        'biometric_enrolled_at',
        'fhir_resource',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
        'active' => 'boolean',
        'biometric_enrolled' => 'boolean',
        'biometric_enrolled_at' => 'datetime',
        'fhir_resource' => 'array',
        // Encrypted fields for Ley 1581 compliance
        'email' => EncryptedCast::class,
        'phone' => EncryptedCast::class,
    ];

    /**
     * Get the full name of the patient.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the general practitioner for this patient.
     */
    public function generalPractitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'general_practitioner_id');
    }

    /**
     * Get the encounters for this patient.
     */
    public function encounters(): HasMany
    {
        return $this->hasMany(Encounter::class);
    }

    /**
     * Get the observations for this patient.
     */
    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class);
    }

    /**
     * Get the conditions for this patient.
     */
    public function conditions(): HasMany
    {
        return $this->hasMany(Condition::class);
    }

    /**
     * Get the medication requests for this patient.
     */
    public function medicationRequests(): HasMany
    {
        return $this->hasMany(MedicationRequest::class);
    }

    /**
     * Get the service requests for this patient.
     */
    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    /**
     * Get the allergy intolerances for this patient.
     */
    public function allergyIntolerances(): HasMany
    {
        return $this->hasMany(AllergyIntolerance::class);
    }

    /**
     * Get the fingerprints for this patient.
     */
    public function fingerprints(): HasMany
    {
        return $this->hasMany(PatientFingerprint::class);
    }

    /**
     * Convert the patient to a FHIR resource.
     *
     * @return array FHIR R4 Patient resource
     */
    public function toFhirResource(): array
    {
        // Si ya tenemos el recurso FHIR almacenado, usarlo
        if ($this->fhir_resource) {
            return $this->fhir_resource;
        }

        // Si no, transformar usando el transformer
        // Esto se implementará en Phase 3
        return [];
    }

    /**
     * Sync the model with a FHIR resource.
     *
     * @param  array  $fhirResource  FHIR R4 Patient resource
     */
    public function syncFhirResource(array $fhirResource): void
    {
        $this->update([
            'fhir_resource' => $fhirResource,
        ]);
    }
}
