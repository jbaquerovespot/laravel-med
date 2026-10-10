<?php

namespace App\Services\Clinical;

use App\Contracts\AllergyChecker;
use App\Models\AllergyIntolerance;
use App\Models\Patient;

class AllergyCheckService implements AllergyChecker
{
    /**
     * Check if patient is allergic to a medication.
     */
    public function check(Patient $patient, string $medicationCode): array
    {
        $allergies = $this->getAllergies($patient);

        foreach ($allergies as $allergy) {
            $allergyCode = $allergy->code ?? $allergy->note ?? '';

            // Check for exact match or partial match
            if ($this->matchesAllergy($medicationCode, $allergyCode)) {
                return [
                    'allergic' => true,
                    'allergy' => $allergy,
                    'severity' => $allergy->severity ?? 'moderate',
                    'reaction' => $allergy->reaction ?? $allergy->note ?? 'Reacción alérgica registrada',
                ];
            }
        }

        return [
            'allergic' => false,
            'allergy' => null,
            'severity' => null,
            'reaction' => null,
        ];
    }

    /**
     * Get all allergies for a patient.
     */
    public function getAllergies(Patient $patient): array
    {
        return $patient->allergyIntolerances()
            ->where('clinical_status', 'active')
            ->get()
            ->all();
    }

    /**
     * Check if medication code matches allergy.
     */
    protected function matchesAllergy(string $medicationCode, string $allergyCode): bool
    {
        $medicationLower = strtolower($medicationCode);
        $allergyLower = strtolower($allergyCode);

        // Exact match
        if ($medicationLower === $allergyLower) {
            return true;
        }

        // Partial match (medication contains allergy)
        if (str_contains($medicationLower, $allergyLower)) {
            return true;
        }

        // Partial match (allergy contains medication)
        if (str_contains($allergyLower, $medicationLower)) {
            return true;
        }

        // Common drug class mappings
        $drugClasses = [
            'penicillin' => ['amoxicillin', 'ampicillin', 'penicillin', 'amoxil', 'augmentin'],
            'sulfa' => ['sulfamethoxazole', 'sulfonamide', 'bactrim', 'septra', 'trimethoprim-sulfamethoxazole'],
            'nsaid' => ['ibuprofen', 'naproxen', 'aspirin', 'diclofenac', 'celecoxib', 'ketorolac'],
            'ace inhibitor' => ['enalapril', 'lisinopril', 'ramipril', 'captopril', 'benazepril'],
            'statin' => ['atorvastatin', 'simvastatin', 'rosuvastatin', 'pravastatin'],
        ];

        foreach ($drugClasses as $class => $drugs) {
            $allergyInClass = false;
            $medicationInClass = false;

            // Check if allergy is the class name itself
            if (str_contains($allergyLower, $class) || str_contains($class, $allergyLower)) {
                $allergyInClass = true;
            }

            foreach ($drugs as $drug) {
                // Check if allergy matches any drug in class
                if (! $allergyInClass && (str_contains($allergyLower, $drug) || str_contains($drug, $allergyLower))) {
                    $allergyInClass = true;
                }
                // Check if medication matches any drug in class
                if (str_contains($medicationLower, $drug) || str_contains($drug, $medicationLower)) {
                    $medicationInClass = true;
                }
            }

            if ($allergyInClass && $medicationInClass) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a new allergy record.
     */
    public function createAllergy(Patient $patient, array $data): AllergyIntolerance
    {
        return AllergyIntolerance::create([
            'patient_id' => $patient->id,
            'code' => $data['code'] ?? null,
            'note' => $data['note'] ?? null,
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
            'severity' => $data['severity'] ?? 'moderate',
            'reaction' => $data['reaction'] ?? null,
        ]);
    }
}
