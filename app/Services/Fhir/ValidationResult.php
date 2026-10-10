<?php

namespace App\Services\Fhir;

use Illuminate\Support\Collection;

/**
 * Validation result for FHIR resource validation.
 * 
 * Encapsulates validation status and errors.
 */
class ValidationResult
{
    /**
     * Create a new validation result.
     *
     * @param bool $valid Whether validation passed
     * @param Collection $errors Collection of error messages
     */
    public function __construct(
        public readonly bool $valid,
        public readonly Collection $errors = new Collection()
    ) {}

    /**
     * Check if validation passed.
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * Get all validation errors.
     *
     * @return array Array of error messages
     */
    public function getErrors(): array
    {
        return $this->errors->toArray();
    }

    /**
     * Get error messages as strings.
     *
     * @return array Array of error message strings
     */
    public function getErrorMessages(): array
    {
        return $this->errors->map(fn($e) => (string) $e)->toArray();
    }

    /**
     * Check if validation failed.
     */
    public function isInvalid(): bool
    {
        return !$this->valid;
    }

    /**
     * Add an error to the result.
     *
     * @param string $error Error message
     * @return self
     */
    public function addError(string $error): self
    {
        $this->errors->push($error);
        return $this;
    }
}
