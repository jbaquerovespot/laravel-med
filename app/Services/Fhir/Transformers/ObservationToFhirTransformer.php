<?php

namespace App\Services\Fhir\Transformers;

use App\Models\Observation;
use Illuminate\Support\Collection;

class ObservationToFhirTransformer implements FhirTransformerInterface
{
    /**
     * Transform Observation model to FHIR Observation resource.
     *
     * @param  Observation  $observation  Observation model instance
     * @return array FHIR R4 Observation resource
     */
    public function transform(object $observation): array
    {
        if (! $observation instanceof Observation) {
            throw new \InvalidArgumentException('Expected Observation model');
        }

        $resource = [
            'resourceType' => 'Observation',
            'id' => (string) $observation->id,
            'status' => $observation->status ?? 'final',
            'category' => $observation->category ? [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                            'code' => $observation->category,
                            'display' => $this->getDisplayForCategory($observation->category),
                        ],
                    ],
                ],
            ] : [],
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://loinc.org',
                        'code' => $observation->code,
                        'display' => $observation->display,
                    ],
                ],
                'text' => $observation->display,
            ],
            'subject' => [
                'reference' => "Patient/{$observation->patient_id}",
                'display' => $observation->patient?->full_name ?? "Patient {$observation->patient_id}",
            ],
            'encounter' => $observation->encounter_id ? [
                'reference' => "Encounter/{$observation->encounter_id}",
            ] : null,
            'effectiveDateTime' => $observation->effective_datetime?->toIso8601String(),
            'issued' => $observation->created_at?->toIso8601String(),
        ];

        // Add value based on type
        if ($observation->value_type === 'Quantity') {
            $resource['valueQuantity'] = [
                'value' => (float) $observation->value_quantity,
                'unit' => $observation->value_unit,
                'system' => 'http://unitsofmeasure.org',
                'code' => $observation->value_unit_code ?? $observation->value_unit,
            ];

            // Add reference range if exists
            if ($observation->reference_range_low || $observation->reference_range_high) {
                $resource['referenceRange'] = [
                    [
                        'low' => $observation->reference_range_low ? [
                            'value' => (float) $observation->reference_range_low,
                            'unit' => $observation->value_unit,
                        ] : null,
                        'high' => $observation->reference_range_high ? [
                            'value' => (float) $observation->reference_range_high,
                            'unit' => $observation->value_unit,
                        ] : null,
                    ],
                ];
            }

            // Add interpretation
            if ($observation->interpretation) {
                $resource['interpretation'] = [
                    [
                        'coding' => [
                            [
                                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ObservationInterpretation',
                                'code' => $this->getInterpretationCode($observation->interpretation),
                                'display' => $observation->interpretation,
                            ],
                        ],
                    ],
                ];
            }
        } elseif ($observation->value_type === 'String') {
            $resource['valueString'] = $observation->value_string;
        }

        // Add components (for composite observations like blood pressure)
        if ($observation->components->isNotEmpty()) {
            $resource['component'] = $this->buildComponents($observation->components);
        }

        return array_filter($resource, fn ($value) => $value !== null && $value !== []);
    }

    /**
     * Transform FHIR Observation resource to Observation model.
     *
     * @param  array  $fhirResource  FHIR R4 Observation resource
     * @return Observation Observation model instance (not persisted)
     */
    public function transformFromFhir(array $fhirResource): object
    {
        $valueType = $this->determineValueType($fhirResource);

        return new Observation([
            'status' => $fhirResource['status'] ?? 'final',
            'category' => $fhirResource['category'][0]['coding'][0]['code'] ?? null,
            'code' => $fhirResource['code']['coding'][0]['code'] ?? '',
            'display' => $fhirResource['code']['coding'][0]['display'] ?? $fhirResource['code']['text'] ?? '',
            'patient_id' => $this->extractResourceId($fhirResource['subject']['reference'] ?? ''),
            'encounter_id' => $this->extractResourceId($fhirResource['encounter']['reference'] ?? ''),
            'effective_datetime' => $fhirResource['effectiveDateTime'] ?? null,
            'value_type' => $valueType,
            'value_quantity' => $fhirResource['valueQuantity']['value'] ?? null,
            'value_unit' => $fhirResource['valueQuantity']['unit'] ?? null,
            'value_string' => $fhirResource['valueString'] ?? null,
            'interpretation' => $fhirResource['interpretation'][0]['coding'][0]['code'] ?? null,
            'reference_range_low' => $fhirResource['referenceRange'][0]['low']['value'] ?? null,
            'reference_range_high' => $fhirResource['referenceRange'][0]['high']['value'] ?? null,
        ]);
    }

    /**
     * Build components array from Observation components.
     */
    protected function buildComponents(Collection $components): array
    {
        return $components->map(function ($component) {
            return [
                'code' => [
                    'coding' => [
                        [
                            'system' => 'http://loinc.org',
                            'code' => $component->code,
                            'display' => $component->display,
                        ],
                    ],
                ],
                'valueQuantity' => [
                    'value' => (float) $component->value_quantity,
                    'unit' => $component->value_unit,
                    'system' => 'http://unitsofmeasure.org',
                    'code' => $component->value_unit_code,
                ],
            ];
        })->toArray();
    }

    /**
     * Determine value type from FHIR resource.
     */
    protected function determineValueType(array $fhirResource): string
    {
        if (isset($fhirResource['valueQuantity'])) {
            return 'Quantity';
        } elseif (isset($fhirResource['valueString'])) {
            return 'String';
        } elseif (isset($fhirResource['valueCodeableConcept'])) {
            return 'CodeableConcept';
        }

        return 'Quantity'; // Default
    }

    /**
     * Get display for observation category.
     */
    protected function getDisplayForCategory(string $category): string
    {
        $displays = [
            'vital-signs' => 'Vital Signs',
            'laboratory' => 'Laboratory',
            'imaging' => 'Imaging',
            'survey' => 'Survey',
            'exam' => 'Exam',
        ];

        return $displays[$category] ?? ucfirst($category);
    }

    /**
     * Get interpretation code.
     */
    protected function getInterpretationCode(string $interpretation): string
    {
        $codes = [
            'normal' => 'N',
            'high' => 'H',
            'low' => 'L',
            'abnormal' => 'A',
            'critically-high' => 'HH',
            'critically-low' => 'LL',
        ];

        return $codes[$interpretation] ?? 'N';
    }

    /**
     * Extract resource ID from FHIR reference.
     */
    protected function extractResourceId(string $reference): ?int
    {
        $parts = explode('/', $reference);
        $id = end($parts);

        return is_numeric($id) ? (int) $id : null;
    }
}
