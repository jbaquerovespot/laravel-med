<?php

namespace App\Services\Fhir\Transformers;

/**
 * Interface for bidirectional FHIR transformers.
 *
 * Each FHIR resource type (Patient, Encounter, Observation, etc.)
 * must have a transformer that implements this interface.
 */
interface FhirTransformerInterface
{
    /**
     * Transform Laravel model to FHIR R4 resource.
     *
     * @param  object  $model  Eloquent model instance
     * @return array FHIR R4 resource as associative array
     */
    public function transform(object $model): array;

    /**
     * Transform FHIR R4 resource to Laravel model.
     *
     * @param  array  $fhirResource  FHIR R4 resource as associative array
     * @return object Eloquent model instance (not persisted)
     */
    public function transformFromFhir(array $fhirResource): object;
}
