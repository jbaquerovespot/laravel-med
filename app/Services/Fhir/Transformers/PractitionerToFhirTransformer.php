<?php

namespace App\Services\Fhir\Transformers;

use App\Models\User;

class PractitionerToFhirTransformer implements FhirTransformerInterface
{
    public function transform(object $model): array
    {
        if (! $model instanceof User) {
            throw new \InvalidArgumentException('Expected User model');
        }

        return [
            'resourceType' => 'Practitioner',
            'id' => (string) $model->id,
            'identifier' => [
                [
                    'use' => 'official',
                    'type' => [
                        'coding' => [
                            [
                                'system' => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                                'code' => 'NPI',
                                'display' => 'National Provider Identifier',
                            ],
                        ],
                    ],
                    'value' => $model->id,
                ],
            ],
            'active' => true,
            'name' => [
                [
                    'use' => 'official',
                    'text' => $model->name,
                    'family' => $this->extractLastName($model->name),
                    'given' => $this->extractGivenNames($model->name),
                ],
            ],
            'telecom' => [
                [
                    'system' => 'email',
                    'value' => $model->email,
                    'use' => 'work',
                ],
            ],
            'qualification' => $model->practitioner ? [
                [
                    'identifier' => [[
                        'value' => $model->practitioner->nmp ?? '',
                        'type' => [
                            'coding' => [[
                                'system' => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                                'code' => 'MD',
                                'display' => 'Medical License Number',
                            ]],
                        ],
                    ]],
                    'code' => [
                        'text' => $model->practitioner->specialty ?? 'General Practice',
                    ],
                ],
            ] : null,
        ];
    }

    public function transformFromFhir(array $fhirResource): object
    {
        $name = $fhirResource['name'][0] ?? [];
        $telecom = collect($fhirResource['telecom'] ?? []);

        return new User([
            'name' => $name['text'] ?? ($name['given'][0] ?? '').' '.($name['family'] ?? ''),
            'email' => $telecom->firstWhere('system', 'email')['value'] ?? null,
        ]);
    }

    protected function extractGivenNames(string $fullName): array
    {
        $parts = explode(' ', trim($fullName));
        array_pop($parts); // Remove last name

        return $parts ?: [$fullName];
    }

    protected function extractLastName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));

        return end($parts) ?? $fullName;
    }
}
