<?php

namespace App\Contracts;

use App\Models\Patient;

/**
 * Interface for checking patient allergies against medications.
 */
interface AllergyChecker
{
    /**
     * Check if patient is allergic to a specific medication.
     *
     * @param  Patient  $patient  The patient to check
     * @param  string  $medicationCode  RxNorm code for the medication
     * @return array{allergic: bool, allergies: array} Result with allergy details
     */
    public function check(Patient $patient, string $medicationCode): array;

    /**
     * Get all recorded allergies for a patient.
     *
     * @param  Patient  $patient  The patient
     * @return array Array of AllergyIntolerance data
     */
    public function getAllergies(Patient $patient): array;
}
