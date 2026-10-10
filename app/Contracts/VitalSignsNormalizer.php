<?php

namespace App\Contracts;

/**
 * Interface for normalizing and interpreting vital signs.
 */
interface VitalSignsNormalizer
{
    /**
     * Normalize vital sign value to standard units.
     *
     * @param  float  $value  The value to normalize
     * @param  string  $fromUnit  Current unit
     * @param  string  $toUnit  Target unit
     * @return float Normalized value
     */
    public function normalize(float $value, string $fromUnit, string $toUnit): float;

    /**
     * Get reference range for vital sign based on patient demographics.
     *
     * @param  string  $loincCode  LOINC code for the vital sign
     * @param  int  $age  Patient age in years
     * @param  string  $gender  Patient gender ('male', 'female', 'other')
     * @return array{low: float, high: float, unit: string} Reference range
     */
    public function getReferenceRange(string $loincCode, int $age, string $gender): array;

    /**
     * Interpret vital sign value relative to reference range.
     *
     * @param  float  $value  The measured value
     * @param  string  $loincCode  LOINC code for the vital sign
     * @param  int  $age  Patient age in years
     * @param  string  $gender  Patient gender ('male', 'female', 'other')
     * @return string Interpretation: 'low', 'normal', 'high', 'critical-low', 'critical-high'
     */
    public function interpret(float $value, string $loincCode, int $age, string $gender): string;
}
