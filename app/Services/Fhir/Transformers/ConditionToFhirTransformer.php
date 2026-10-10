<?php

namespace App\Services\Fhir\Transformers;

use App\Models\Condition;

class ConditionToFhirTransformer implements FhirTransformerInterface
{
    public function transform(object $model): array
    {
        if (! $model instanceof Condition) {
            throw new \InvalidArgumentException('Expected Condition model');
        }

        return [
            'resourceType' => 'Condition',
            'id' => (string) $model->id,
            'clinicalStatus' => [
                'coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
                    'code' => $model->clinical_status ?? 'active',
                ]],
            ],
            'verificationStatus' => [
                'coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/condition-ver-status',
                    'code' => $model->verification_status ?? 'unconfirmed',
                ]],
            ],
            'code' => [
                'coding' => [[
                    'system' => 'http://snomed.info/sct',
                    'code' => $model->code,
                    'display' => $model->display,
                ]],
            ],
            'subject' => [
                'reference' => "Patient/{$model->patient_id}",
                'display' => $model->patient?->full_name,
            ],
            'encounter' => $model->encounter_id ? [
                'reference' => "Encounter/{$model->encounter_id}",
            ] : null,
            'onsetDateTime' => $model->onset_datetime?->toIso8601String(),
            'abatementDateTime' => $model->abatement_datetime?->toIso8601String(),
            'recorder' => $model->recorder_id ? [
                'reference' => "Practitioner/{$model->recorder_id}",
            ] : null,
        ];
    }

    public function transformFromFhir(array $fhirResource): object
    {
        return new Condition([
            'clinical_status' => $fhirResource['clinicalStatus']['coding'][0]['code'] ?? 'active',
            'verification_status' => $fhirResource['verificationStatus']['coding'][0]['code'] ?? 'unconfirmed',
            'code' => $fhirResource['code']['coding'][0]['code'] ?? '',
            'display' => $fhirResource['code']['coding'][0]['display'] ?? '',
            'patient_id' => $this->extractResourceId($fhirResource['subject']['reference'] ?? ''),
            'encounter_id' => $this->extractResourceId($fhirResource['encounter']['reference'] ?? ''),
            'onset_datetime' => $fhirResource['onsetDateTime'] ?? null,
            'recorder_id' => $this->extractResourceId($fhirResource['recorder']['reference'] ?? ''),
        ]);
    }

    protected function extractResourceId(string $reference): ?int
    {
        $parts = explode('/', $reference);
        $id = end($parts);

        return is_numeric($id) ? (int) $id : null;
    }
}
