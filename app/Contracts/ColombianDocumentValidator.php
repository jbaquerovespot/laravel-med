<?php

namespace App\Contracts;

/**
 * Interface for validating Colombian document numbers.
 */
interface ColombianDocumentValidator
{
    /**
     * Validate a Colombian document number.
     *
     * @param  string  $documentNumber  The document number to validate
     * @param  string  $type  Document type: 'CC', 'TI', 'PA', 'RC'
     * @return bool True if valid
     */
    public function validate(string $documentNumber, string $type = 'CC'): bool;

    /**
     * Detect document type from number format.
     *
     * @param  string  $documentNumber  The document number
     * @return string|null Document type or null if unknown
     */
    public function detectType(string $documentNumber): ?string;
}
