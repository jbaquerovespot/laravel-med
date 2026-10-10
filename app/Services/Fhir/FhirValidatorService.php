<?php

namespace App\Services\Fhir;

use Illuminate\Support\Collection;

/**
 * Service for validating FHIR resources (3-level validation).
 */
class FhirValidatorService
{
    protected array $requiredFields = [
        'Patient' => ['identifier', 'name', 'gender', 'birthDate'],
        'Encounter' => ['status', 'subject', 'period'],
        'Observation' => ['status', 'code', 'subject', 'effectiveDateTime'],
        'Condition' => ['clinicalStatus', 'verificationStatus', 'code', 'subject'],
        'MedicationRequest' => ['status', 'intent', 'medicationCodeableConcept', 'subject'],
        'ServiceRequest' => ['status', 'intent', 'code', 'subject'],
        'AllergyIntolerance' => ['clinicalStatus', 'verificationStatus', 'type', 'code', 'patient'],
        'Practitioner' => ['name'],
    ];

    public function validate(array $resource, string $resourceType): ValidationResult
    {
        $errors = collect();

        // Level 1: Structure validation
        $structureErrors = $this->validateStructure($resource, $resourceType);
        if ($structureErrors->isNotEmpty()) {
            return new ValidationResult(false, $structureErrors);
        }

        // Level 2: Schema validation
        if (config('fhir.validation.enable_schema_validation', true)) {
            $schemaErrors = $this->validateAgainstSchema($resource, $resourceType);
            $errors = $errors->merge($schemaErrors);
        }

        // Level 3: Business rules validation
        if (config('fhir.validation.enable_business_rules', true)) {
            $businessErrors = $this->validateBusinessRules($resource, $resourceType);
            $errors = $errors->merge($businessErrors);
        }

        return new ValidationResult($errors->isEmpty(), $errors);
    }

    protected function validateStructure(array $resource, string $resourceType): Collection
    {
        $errors = collect();

        if (!isset($resource['resourceType'])) {
            $errors->push('Missing required field: resourceType');
        } elseif ($resource['resourceType'] !== $resourceType) {
            $errors->push("resourceType mismatch: expected {$resourceType}, got {$resource['resourceType']}");
        }

        foreach ($this->getRequiredFields($resourceType) as $field) {
            if (!isset($resource[$field])) {
                $errors->push("Missing required field for {$resourceType}: {$field}");
            }
        }

        return $errors;
    }

    protected function validateAgainstSchema(array $resource, string $resourceType): Collection
    {
        $errors = collect();

        // Basic format validation
        if ($resourceType === 'Patient' && isset($resource['birthDate'])) {
            if (!$this->isValidDateFormat($resource['birthDate'])) {
                $errors->push('birthDate must be in YYYY-MM-DD format');
            }
        }

        if ($resourceType === 'Observation' && isset($resource['effectiveDateTime'])) {
            try {
                new \DateTime($resource['effectiveDateTime']);
            } catch (\Exception $e) {
                $errors->push('effectiveDateTime must be in ISO8601 format');
            }
        }

        return $errors;
    }

    protected function validateBusinessRules(array $resource, string $resourceType): Collection
    {
        $errors = collect();

        if ($resourceType === 'Patient') {
            // Validate Colombian cédula
            if (isset($resource['identifier'][0]['value'])) {
                $identifier = $resource['identifier'][0]['value'];
                if (!preg_match('/^\d{6,12}$/', $identifier)) {
                    $errors->push('Invalid Colombian document number format (expected 6-12 digits)');
                }
            }

            // Validate birthDate not in future
            if (isset($resource['birthDate'])) {
                $birthDate = \DateTime::createFromFormat('Y-m-d', $resource['birthDate']);
                if (!$birthDate) {
                    $errors->push('Invalid birthDate format (expected YYYY-MM-DD)');
                } elseif ($birthDate > new \DateTime()) {
                    $errors->push('birthDate cannot be in the future');
                }
            }

            // Validate gender
            if (isset($resource['gender'])) {
                $validGenders = ['male', 'female', 'other', 'unknown'];
                if (!in_array($resource['gender'], $validGenders)) {
                    $errors->push("Invalid gender: {$resource['gender']}");
                }
            }
        }

        if ($resourceType === 'Observation') {
            // Validate LOINC system
            if (isset($resource['code']['coding'][0]['system'])) {
                if ($resource['code']['coding'][0]['system'] !== 'http://loinc.org') {
                    $errors->push('Observation code must use LOINC system');
                }
            }
        }

        if ($resourceType === 'Encounter') {
            // Validate period
            if (isset($resource['period']['start']) && isset($resource['period']['end'])) {
                $start = new \DateTime($resource['period']['start']);
                $end = new \DateTime($resource['period']['end']);
                if ($start > $end) {
                    $errors->push('Period start cannot be after period end');
                }
            }
        }

        return $errors;
    }

    public function getRequiredFields(string $resourceType): array
    {
        return $this->requiredFields[$resourceType] ?? [];
    }

    protected function isValidDateFormat(string $date): bool
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
        return $dateTime && $dateTime->format('Y-m-d') === $date;
    }
}
