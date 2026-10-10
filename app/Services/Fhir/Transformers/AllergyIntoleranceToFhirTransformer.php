<?php

namespace App\Services\Fhir\Transformers;

use App\Models\AllergyIntolerance;

class AllergyIntoleranceToFhirTransformer implements FhirTransformerInterface
{
    public function transform(object $model): array
    {
        if (! $model instanceof AllergyIntolerance) {
            throw new \InvalidArgumentException('Expected AllergyIntolerance model');
        }

        return [
            'resourceType' => 'AllergyIntolerance',
            'id' => (string) $model->id,
            'clinicalStatus' => [
                'coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/allergyintolerance-clinical',
                    'code' => $model->clinical_status ?? 'active',
                ]],
            ],
            'verificationStatus' => [
                'coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/allergyintolerance-verification',
                    'code' => $model->verification_status ?? 'unconfirmed',
                ]],
            ],
            'type' => $model->type ?? 'allergy',
            'category' => $model->category ? [$model->category] : [],
            'code' => [
                'coding' => [[
                    'system' => 'http://snomed.info/sct',
                    'code' => $model->code,
                    'display' => $model->display,
                ]],
            ],
            'patient' => [
                'reference' => "Patient/{$model->patient_id}",
                'display' => $model->patient?->full_name,
            ],
            'recordedDate' => $model->created_at?->toIso8601String(),
            'recorder' => $model->recorder_id ? [
                'reference' => "Practitioner/{$model->recorder_id}",
            ] : null,
            'criticality' => $model->criticality ?? 'unable-to-assess',
            'reaction' => $this->buildReaction($model),
            'note' => $model->note ? [['text' => $model->note]] : null,
        ];
    }

    public function transformFromFhir(array $fhirResource): object
    {
        return new AllergyIntolerance([
            'clinical_status' => $fhirResource['clinicalStatus']['coding'][0]['code'] ?? 'active',
            'verification_status' => $fhirResource['verificationStatus']['coding'][0]['code'] ?? 'unconfirmed',
            'type' => $fhirResource['type'] ?? 'allergy',
            'category' => $fhirResource['category'][0] ?? null,
            'code' => $fhirResource['code']['coding'][0]['code'] ?? '',
            'display' => $fhirResource['code']['coding'][0]['display'] ?? '',
            'patient_id' => $this->extractResourceId($fhirResource['patient']['reference'] ?? ''),
            'recorder_id' => $this->extractResourceId($fhirResource['recorder']['reference'] ?? ''),
            'criticality' => $fhirResource['criticality'] ?? 'unable-to-assess',
        ]);
    }

    protected function buildReaction(AllergyIntolerance $model): ?array
    {
        if (! $model->reaction_description) {
            return null;
        }

        return [[
            'description' => $model->reaction_description,
            'severity' => $model->reaction_severity ?? 'moderate',
            'onset' => $model->onset_datetime?->toIso8601String(),
        ]];
    }

    protected function extractResourceId(string $reference): ?int
    {
        $parts = explode('/', $reference);
        $id = end($parts);

        return is_numeric($id) ? (int) $id : null;
    }
}
