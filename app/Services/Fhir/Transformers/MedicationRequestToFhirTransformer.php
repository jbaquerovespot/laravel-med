<?php

namespace App\Services\Fhir\Transformers;

use App\Models\MedicationRequest;

class MedicationRequestToFhirTransformer implements FhirTransformerInterface
{
    public function transform(object $model): array
    {
        if (! $model instanceof MedicationRequest) {
            throw new \InvalidArgumentException('Expected MedicationRequest model');
        }

        return [
            'resourceType' => 'MedicationRequest',
            'id' => (string) $model->id,
            'status' => $model->status ?? 'active',
            'intent' => $model->intent ?? 'order',
            'medicationCodeableConcept' => [
                'coding' => [[
                    'system' => 'http://www.nlm.nih.gov/research/umls/rxnorm',
                    'code' => $model->medication_code,
                    'display' => $model->medication_display,
                ]],
            ],
            'subject' => [
                'reference' => "Patient/{$model->patient_id}",
                'display' => $model->patient?->full_name,
            ],
            'encounter' => $model->encounter_id ? [
                'reference' => "Encounter/{$model->encounter_id}",
            ] : null,
            'authoredOn' => $model->authored_on?->toIso8601String(),
            'requester' => $model->requester_id ? [
                'reference' => "Practitioner/{$model->requester_id}",
                'display' => $model->requester?->name,
            ] : null,
            'dosageInstruction' => $this->buildDosage($model),
        ];
    }

    public function transformFromFhir(array $fhirResource): object
    {
        return new MedicationRequest([
            'status' => $fhirResource['status'] ?? 'active',
            'intent' => $fhirResource['intent'] ?? 'order',
            'medication_code' => $fhirResource['medicationCodeableConcept']['coding'][0]['code'] ?? '',
            'medication_display' => $fhirResource['medicationCodeableConcept']['coding'][0]['display'] ?? '',
            'patient_id' => $this->extractResourceId($fhirResource['subject']['reference'] ?? ''),
            'encounter_id' => $this->extractResourceId($fhirResource['encounter']['reference'] ?? ''),
            'authored_on' => $fhirResource['authoredOn'] ?? null,
            'requester_id' => $this->extractResourceId($fhirResource['requester']['reference'] ?? ''),
            'dosage_instruction' => $fhirResource['dosageInstruction'] ?? null,
        ]);
    }

    protected function buildDosage(MedicationRequest $model): ?array
    {
        if (! $model->dosage_instruction) {
            return null;
        }

        return is_array($model->dosage_instruction)
            ? $model->dosage_instruction
            : [['text' => $model->dosage_instruction]];
    }

    protected function extractResourceId(string $reference): ?int
    {
        $parts = explode('/', $reference);
        $id = end($parts);

        return is_numeric($id) ? (int) $id : null;
    }
}
