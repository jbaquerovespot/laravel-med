<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Condition extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'encounter_id',
        'recorder_id',
        'clinical_status',
        'verification_status',
        'code',
        'display',
        'category',
        'onset_datetime',
        'abatement_datetime',
        'note',
        'fhir_resource',
    ];

    protected $casts = [
        'onset_datetime' => 'datetime',
        'abatement_datetime' => 'datetime',
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

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorder_id');
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
