<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Observation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'encounter_id',
        'status',
        'category',
        'code',
        'display',
        'effective_datetime',
        'value_type',
        'value_quantity',
        'value_unit',
        'value_string',
        'interpretation',
        'reference_range_low',
        'reference_range_high',
        'fhir_resource',
    ];

    protected $casts = [
        'effective_datetime' => 'datetime',
        'value_quantity' => 'float',
        'reference_range_low' => 'float',
        'reference_range_high' => 'float',
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

    public function components(): HasMany
    {
        return $this->hasMany(ObservationComponent::class);
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
