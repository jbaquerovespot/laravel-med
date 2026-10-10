<?php

namespace App\Services\Fhir\Transformers;

use App\Models\ServiceRequest;

class ServiceRequestToFhirTransformer implements FhirTransformerInterface
{
    public function transform(object $model): array
    {
        if (! $model instanceof ServiceRequest) {
            throw new \InvalidArgumentException('Expected ServiceRequest model');
        }

        return [
            'resourceType' => 'ServiceRequest',
            'id' => (string) $model->id,
            'status' => $model->status ?? 'draft',
            'intent' => $model->intent ?? 'order',
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
            'authoredOn' => $model->authored_on?->toIso8601String(),
            'scheduledDateTime' => $model->scheduled_datetime?->toIso8601String(),
            'performer' => $model->performer_id ? [[
                'reference' => "Practitioner/{$model->performer_id}",
                'display' => $model->performer?->name,
            ]] : null,
            'requester' => $model->requester_id ? [
                'reference' => "Practitioner/{$model->requester_id}",
            ] : null,
        ];
    }

    public function transformFromFhir(array $fhirResource): object
    {
        return new ServiceRequest([
            'status' => $fhirResource['status'] ?? 'draft',
            'intent' => $fhirResource['intent'] ?? 'order',
            'code' => $fhirResource['code']['coding'][0]['code'] ?? '',
            'display' => $fhirResource['code']['coding'][0]['display'] ?? '',
            'patient_id' => $this->extractResourceId($fhirResource['subject']['reference'] ?? ''),
            'encounter_id' => $this->extractResourceId($fhirResource['encounter']['reference'] ?? ''),
            'authored_on' => $fhirResource['authoredOn'] ?? null,
            'scheduled_datetime' => $fhirResource['scheduledDateTime'] ?? null,
            'performer_id' => $this->extractResourceId($fhirResource['performer'][0]['reference'] ?? ''),
            'requester_id' => $this->extractResourceId($fhirResource['requester']['reference'] ?? ''),
        ]);
    }

    protected function extractResourceId(string $reference): ?int
    {
        $parts = explode('/', $reference);
        $id = end($parts);

        return is_numeric($id) ? (int) $id : null;
    }
}
