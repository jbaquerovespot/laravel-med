<?php

namespace App\Services\Fhir\Transformers;

use App\Models\Encounter;

class EncounterToFhirTransformer implements FhirTransformerInterface
{
    /**
     * Transform Encounter model to FHIR Encounter resource.
     *
     * @param  Encounter  $encounter  Encounter model instance
     * @return array FHIR R4 Encounter resource
     */
    public function transform(object $encounter): array
    {
        if (! $encounter instanceof Encounter) {
            throw new \InvalidArgumentException('Expected Encounter model');
        }

        return [
            'resourceType' => 'Encounter',
            'id' => (string) $encounter->id,
            'meta' => [
                'lastUpdated' => $encounter->updated_at?->toIso8601String() ?? now()->toIso8601String(),
            ],
            'status' => $encounter->status ?? 'finished',
            'class' => [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => $encounter->class_code ?? 'AMB',
                'display' => $encounter->class_display ?? 'ambulatory',
            ],
            'serviceType' => [
                'coding' => [
                    [
                        'system' => 'http://snomed.info/sct',
                        'code' => $encounter->service_type_code ?? '185349003',
                        'display' => $encounter->service_type_display ?? 'Encounter for check up',
                    ],
                ],
            ],
            'subject' => [
                'reference' => "Patient/{$encounter->patient_id}",
                'display' => $encounter->patient?->full_name ?? "Patient {$encounter->patient_id}",
            ],
            'participant' => [
                [
                    'type' => [
                        [
                            'coding' => [
                                [
                                    'system' => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
                                    'code' => 'PPRF',
                                    'display' => 'primary performer',
                                ],
                            ],
                        ],
                    ],
                    'individual' => [
                        'reference' => "Practitioner/{$encounter->practitioner_id}",
                        'display' => $encounter->practitioner?->name ?? "Practitioner {$encounter->practitioner_id}",
                    ],
                ],
            ],
            'period' => [
                'start' => $encounter->start_datetime?->toIso8601String(),
                'end' => $encounter->end_datetime?->toIso8601String(),
            ],
            'reasonCode' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://snomed.info/sct',
                            'code' => $encounter->reason_code ?? 'unknown',
                            'display' => $encounter->reason_text ?? 'Unknown reason',
                        ],
                    ],
                ],
            ],
            'location' => $this->buildLocation($encounter),
        ];
    }

    /**
     * Transform FHIR Encounter resource to Encounter model.
     *
     * @param  array  $fhirResource  FHIR R4 Encounter resource
     * @return Encounter Encounter model instance (not persisted)
     */
    public function transformFromFhir(array $fhirResource): object
    {
        return new Encounter([
            'status' => $fhirResource['status'] ?? 'planned',
            'class_code' => $fhirResource['class']['code'] ?? 'AMB',
            'class_display' => $fhirResource['class']['display'] ?? 'ambulatory',
            'service_type_code' => $fhirResource['serviceType']['coding'][0]['code'] ?? null,
            'service_type_display' => $fhirResource['serviceType']['coding'][0]['display'] ?? null,
            'patient_id' => $this->extractResourceId($fhirResource['subject']['reference'] ?? ''),
            'practitioner_id' => $this->extractResourceId($fhirResource['participant'][0]['individual']['reference'] ?? ''),
            'start_datetime' => $fhirResource['period']['start'] ?? null,
            'end_datetime' => $fhirResource['period']['end'] ?? null,
            'reason_code' => $fhirResource['reasonCode'][0]['coding'][0]['code'] ?? null,
            'reason_text' => $fhirResource['reasonCode'][0]['coding'][0]['display'] ?? null,
        ]);
    }

    /**
     * Build location array from Encounter model.
     */
    protected function buildLocation(Encounter $encounter): array
    {
        if (! $encounter->location) {
            return [];
        }

        return [
            [
                'location' => [
                    'reference' => "Location/{$encounter->location_id}",
                    'display' => $encounter->location->name ?? "Location {$encounter->location_id}",
                ],
            ],
        ];
    }

    /**
     * Extract resource ID from FHIR reference (e.g., "Patient/123" → 123).
     */
    protected function extractResourceId(string $reference): ?int
    {
        $parts = explode('/', $reference);
        $id = end($parts);

        return is_numeric($id) ? (int) $id : null;
    }
}
