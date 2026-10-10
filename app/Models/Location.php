<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'address',
        'city',
        'state',
        'postal_code',
        'phone',
        'fhir_resource',
    ];

    protected $casts = [
        'fhir_resource' => 'array',
    ];

    public function encounters(): HasMany
    {
        return $this->hasMany(Encounter::class);
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
