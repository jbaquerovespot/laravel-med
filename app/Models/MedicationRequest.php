<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicationRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'encounter_id',
        'requester_id',
        'status',
        'intent',
        'medication_code',
        'medication_display',
        'authored_on',
        'dosage_instruction',
        'dispense_quantity',
        'dispense_unit',
        'fhir_resource',
    ];

    protected $casts = [
        'authored_on' => 'datetime',
        'dosage_instruction' => 'array',
        'fhir_resource' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
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
