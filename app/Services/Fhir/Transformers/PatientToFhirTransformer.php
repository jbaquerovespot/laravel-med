<?php

namespace App\Services\Fhir\Transformers;

use App\Models\Patient;

class PatientToFhirTransformer implements FhirTransformerInterface
{
    /**
     * Transform Patient model to FHIR Patient resource.
     *
     * @param  Patient  $patient  Patient model instance
     * @return array FHIR R4 Patient resource
     */
    public function transform(object $patient): array
    {
        if (! $patient instanceof Patient) {
            throw new \InvalidArgumentException('Expected Patient model');
        }

        return [
            'resourceType' => 'Patient',
            'id' => (string) $patient->id,
            'meta' => [
                'versionId' => (string) $patient->updated_at?->timestamp ?? time(),
                'lastUpdated' => $patient->updated_at?->toIso8601String() ?? now()->toIso8601String(),
                'source' => config('app.url'),
            ],
            'identifier' => [
                [
                    'use' => 'official',
                    'type' => [
                        'coding' => [
                            [
                                'system' => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                                'code' => 'NN',
                                'display' => 'National Number',
                            ],
                        ],
                    ],
                    'system' => 'http://hl7.org/fhir/sid/us-ssn',
                    'value' => $patient->document_number,
                ],
            ],
            'active' => $patient->active ?? true,
            'name' => [
                [
                    'use' => 'official',
                    'family' => $patient->last_name,
                    'given' => [$patient->first_name],
                ],
            ],
            'telecom' => $this->buildTelecom($patient),
            'gender' => $patient->gender ?? 'unknown',
            'birthDate' => $patient->birth_date?->format('Y-m-d'),
            'address' => $this->buildAddress($patient),
            'maritalStatus' => $this->buildMaritalStatus($patient),
            'contact' => $this->buildEmergencyContact($patient),
            'generalPractitioner' => $this->buildGeneralPractitioner($patient),
        ];
    }

    /**
     * Transform FHIR Patient resource to Patient model.
     *
     * @param  array  $fhirResource  FHIR R4 Patient resource
     * @return Patient Patient model instance (not persisted)
     */
    public function transformFromFhir(array $fhirResource): object
    {
        $identifier = $fhirResource['identifier'][0]['value'] ?? null;
        $name = $fhirResource['name'][0] ?? [];
        $telecom = collect($fhirResource['telecom'] ?? []);
        $address = $fhirResource['address'][0] ?? [];

        return new Patient([
            'document_number' => $identifier,
            'first_name' => $name['given'][0] ?? '',
            'last_name' => $name['family'] ?? '',
            'gender' => $fhirResource['gender'] ?? 'unknown',
            'birth_date' => $fhirResource['birthDate'] ?? null,
            'email' => $telecom->firstWhere('system', 'email')['value'] ?? null,
            'phone' => $telecom->firstWhere('system', 'phone')['value'] ?? null,
            'address' => $address['line'][0] ?? null,
            'city' => $address['city'] ?? null,
            'postal_code' => $address['postalCode'] ?? null,
            'country' => $address['country'] ?? 'Colombia',
            'active' => $fhirResource['active'] ?? true,
        ]);
    }

    /**
     * Build telecom array from Patient model.
     */
    protected function buildTelecom(Patient $patient): array
    {
        $telecom = [];

        if ($patient->phone) {
            $telecom[] = [
                'system' => 'phone',
                'value' => $patient->phone,
                'use' => 'mobile',
            ];
        }

        if ($patient->email) {
            $telecom[] = [
                'system' => 'email',
                'value' => $patient->email,
            ];
        }

        return $telecom;
    }

    /**
     * Build address array from Patient model.
     */
    protected function buildAddress(Patient $patient): array
    {
        if (! $patient->address) {
            return [];
        }

        return [
            [
                'use' => 'home',
                'type' => 'both',
                'line' => [$patient->address],
                'city' => $patient->city ?? '',
                'state' => $patient->state ?? '',
                'postalCode' => $patient->postal_code ?? '',
                'country' => $patient->country ?? 'Colombia',
            ],
        ];
    }

    /**
     * Build marital status from Patient model.
     */
    protected function buildMaritalStatus(Patient $patient): ?array
    {
        if (! $patient->marital_status) {
            return null;
        }

        return [
            'coding' => [
                [
                    'system' => 'http://terminology.hl7.org/CodeSystem/v3-MaritalStatus',
                    'code' => $patient->marital_status,
                ],
            ],
        ];
    }

    /**
     * Build emergency contact from Patient model.
     */
    protected function buildEmergencyContact(Patient $patient): array
    {
        if (! $patient->emergency_contact_name) {
            return [];
        }

        return [
            [
                'relationship' => [
                    [
                        'coding' => [
                            [
                                'system' => 'http://terminology.hl7.org/CodeSystem/v2-0131',
                                'code' => 'C',
                                'display' => 'Emergency Contact',
                            ],
                        ],
                    ],
                ],
                'name' => [
                    'family' => $this->extractLastName($patient->emergency_contact_name),
                    'given' => [$this->extractFirstName($patient->emergency_contact_name)],
                ],
                'telecom' => [
                    [
                        'system' => 'phone',
                        'value' => $patient->emergency_contact_phone,
                    ],
                ],
            ],
        ];
    }

    /**
     * Build general practitioner reference.
     */
    protected function buildGeneralPractitioner(Patient $patient): ?array
    {
        if (! $patient->generalPractitioner) {
            return null;
        }

        return [
            [
                'reference' => "Practitioner/{$patient->generalPractitioner->id}",
                'display' => $patient->generalPractitioner->name,
            ],
        ];
    }

    /**
     * Extract first name from full name.
     */
    protected function extractFirstName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));

        return $parts[0] ?? $fullName;
    }

    /**
     * Extract last name from full name.
     */
    protected function extractLastName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));

        return end($parts) ?? $fullName;
    }
}
