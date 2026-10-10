<?php

namespace App\Services\Fhir;

use App\Services\Fhir\Transformers\FhirTransformerInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service for transforming between Laravel models and FHIR resources.
 */
class FhirTransformerService
{
    protected array $transformers = [
        'Patient' => \App\Services\Fhir\Transformers\PatientToFhirTransformer::class,
        'Encounter' => \App\Services\Fhir\Transformers\EncounterToFhirTransformer::class,
        'Observation' => \App\Services\Fhir\Transformers\ObservationToFhirTransformer::class,
        'Condition' => \App\Services\Fhir\Transformers\ConditionToFhirTransformer::class,
        'MedicationRequest' => \App\Services\Fhir\Transformers\MedicationRequestToFhirTransformer::class,
        'ServiceRequest' => \App\Services\Fhir\Transformers\ServiceRequestToFhirTransformer::class,
        'AllergyIntolerance' => \App\Services\Fhir\Transformers\AllergyIntoleranceToFhirTransformer::class,
        'Practitioner' => \App\Services\Fhir\Transformers\PractitionerToFhirTransformer::class,
    ];

    public function transform(object $model, string $resourceType): array
    {
        $transformer = $this->getTransformer($resourceType);
        return $transformer->transform($model);
    }

    public function transformToBundle(Collection $models, string $resourceType): array
    {
        $entries = $models->map(function ($model) use ($resourceType) {
            return [
                'fullUrl' => url("/api/fhir/{$resourceType}/{$model->id}"),
                'resource' => $this->transform($model, $resourceType),
            ];
        })->toArray();

        return [
            'resourceType' => 'Bundle',
            'type' => 'searchset',
            'timestamp' => now()->toIso8601String(),
            'total' => $models->count(),
            'entry' => $entries,
        ];
    }

    public function transformFromFhir(array $fhirResource, string $resourceType): object
    {
        $transformer = $this->getTransformer($resourceType);
        return $transformer->transformFromFhir($fhirResource);
    }

    protected function getTransformer(string $resourceType): FhirTransformerInterface
    {
        if (!isset($this->transformers[$resourceType])) {
            throw new \InvalidArgumentException("Unknown resource type: {$resourceType}");
        }

        $transformerClass = $this->transformers[$resourceType];
        return new $transformerClass();
    }

    public function hasTransformer(string $resourceType): bool
    {
        return isset($this->transformers[$resourceType]);
    }
}
