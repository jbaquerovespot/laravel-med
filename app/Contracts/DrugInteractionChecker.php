<?php

namespace App\Contracts;

/**
 * Interface for checking drug-drug interactions.
 */
interface DrugInteractionChecker
{
    /**
     * Check for drug interactions among a list of medications.
     *
     * @param  array  $medications  Array of medication codes (RxNorm)
     * @return array Array of interactions with severity and description
     */
    public function checkInteractions(array $medications): array;

    /**
     * Get interaction details between two specific drugs.
     *
     * @param  string  $drug1  RxNorm code for first drug
     * @param  string  $drug2  RxNorm code for second drug
     * @return array|null Interaction details or null if no interaction
     */
    public function getInteraction(string $drug1, string $drug2): ?array;
}
