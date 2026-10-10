<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validation rule for Colombian document numbers.
 *
 * Supported formats:
 * - CC (Cédula de Ciudadanía): 8-10 digits
 * - TI (Tarjeta de Identidad): format with hyphen (e.g., 123456-7)
 * - PA (Pasaporte): 6-12 alphanumeric characters
 * - RC (Registro Civil): starts with RC followed by 6-10 digits
 */
class ValidColombianDocument implements ValidationRule
{
    /**
     * Document type to validate against.
     * If null, accepts any valid Colombian document format.
     */
    protected ?string $documentType;

    /**
     * Create a new rule instance.
     */
    public function __construct(?string $documentType = null)
    {
        $this->documentType = $documentType;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = trim((string) $value);

        if ($this->isValidDocument($value)) {
            return;
        }

        $fail('El número de documento no tiene un formato válido colombiano.');
    }

    /**
     * Determine if the value is a valid Colombian document.
     */
    protected function isValidDocument(string $value): bool
    {
        // Check RC first (has specific prefix) before PA which matches alphanumeric
        if (preg_match('/^RC\d{6,10}$/i', $value)) {
            return $this->documentType === null || $this->documentType === 'RC';
        }

        // Cédula de Ciudadanía (CC) - 8-10 digits
        if (preg_match('/^\d{8,10}$/', $value)) {
            return $this->documentType === null || $this->documentType === 'CC';
        }

        // Tarjeta de Identidad (TI) - format with hyphen
        if (preg_match('/^\d{6,9}-\d{1,2}$/', $value)) {
            return $this->documentType === null || $this->documentType === 'TI';
        }

        // Pasaporte (PA) - 6-12 alphanumeric characters
        if (preg_match('/^[A-Z0-9]{6,12}$/i', $value)) {
            return $this->documentType === null || $this->documentType === 'PA';
        }

        return false;
    }

    /**
     * Detect the document type from the number format.
     */
    public static function detectType(string $value): ?string
    {
        $value = trim($value);

        // Check RC first (has specific prefix)
        if (preg_match('/^RC\d{6,10}$/i', $value)) {
            return 'RC';
        }

        if (preg_match('/^\d{8,10}$/', $value)) {
            return 'CC';
        }

        if (preg_match('/^\d{6,9}-\d{1,2}$/', $value)) {
            return 'TI';
        }

        if (preg_match('/^[A-Z0-9]{6,12}$/i', $value)) {
            return 'PA';
        }

        return null;
    }

    /**
     * Validate specifically as Cédula de Ciudadanía.
     */
    public static function validateCC(string $value): bool
    {
        return (bool) preg_match('/^\d{8,10}$/', trim($value));
    }

    /**
     * Validate specifically as Tarjeta de Identidad.
     */
    public static function validateTI(string $value): bool
    {
        return (bool) preg_match('/^\d{6,9}-\d{1,2}$/', trim($value));
    }

    /**
     * Validate specifically as Pasaporte.
     */
    public static function validatePA(string $value): bool
    {
        return (bool) preg_match('/^[A-Z0-9]{6,12}$/i', trim($value));
    }
}
