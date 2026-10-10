<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Encounter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'practitioner_id',
        'location_id',
        'status',
        'start_datetime',
        'end_datetime',
        'reason_code',
        'reason_text',
        'class_code',
        'class_display',
        'service_type_code',
        'service_type_display',
        'fhir_resource',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'fhir_resource' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class);
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(Condition::class);
    }

    public function medicationRequests(): HasMany
    {
        return $this->hasMany(MedicationRequest::class);
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function toFhirResource(): array
    {
        if ($this->fhir_resource) {
            return $this->fhir_resource;
        }

        return [];
    }

    public function syncFhirResource(array $fhirResource): void
    {
        $this->update(['fhir_resource' => $fhirResource]);
    }
}
