<?php

namespace App\Services\Clinical;

use App\Contracts\VitalSignsNormalizer;

class ReferenceRangeService implements VitalSignsNormalizer
{
    /**
     * LOINC codes for vital signs.
     */
    protected const LOINC_CODES = [
        'systolic_bp' => '8480-6',
        'diastolic_bp' => '8462-4',
        'heart_rate' => '8867-4',
        'respiratory_rate' => '9279-1',
        'temperature' => '8310-5',
        'spo2' => '59408-5',
        'weight' => '29463-7',
        'height' => '8302-2',
        'bmi' => '39156-5',
    ];

    /**
     * Reference ranges by age group and gender.
     */
    protected array $ranges = [
        'systolic_bp' => [
            'adult' => ['critical_low' => 85, 'low' => 90, 'normal_min' => 90, 'normal_max' => 120, 'high' => 140, 'critical_high' => 180],
            'pediatric' => ['critical_low' => 65, 'low' => 70, 'normal_min' => 80, 'normal_max' => 110, 'high' => 120, 'critical_high' => 140],
        ],
        'diastolic_bp' => [
            'adult' => ['critical_low' => 50, 'low' => 60, 'normal_min' => 60, 'normal_max' => 80, 'high' => 90, 'critical_high' => 120],
            'pediatric' => ['critical_low' => 30, 'low' => 40, 'normal_min' => 50, 'normal_max' => 70, 'high' => 80, 'critical_high' => 100],
        ],
        'heart_rate' => [
            'adult' => ['critical_low' => 50, 'low' => 55, 'normal_min' => 60, 'normal_max' => 100, 'high' => 120, 'critical_high' => 150],
            'pediatric' => ['critical_low' => 50, 'low' => 60, 'normal_min' => 80, 'normal_max' => 140, 'high' => 160, 'critical_high' => 200],
        ],
        'respiratory_rate' => [
            'adult' => ['critical_low' => 8, 'low' => 10, 'normal_min' => 12, 'normal_max' => 20, 'high' => 24, 'critical_high' => 30],
            'pediatric' => ['critical_low' => 12, 'low' => 15, 'normal_min' => 20, 'normal_max' => 30, 'high' => 40, 'critical_high' => 50],
        ],
        'temperature' => [
            'adult' => ['critical_low' => 35.1, 'low' => 35.5, 'normal_min' => 36.0, 'normal_max' => 37.5, 'high' => 38.5, 'critical_high' => 40.0],
            'pediatric' => ['critical_low' => 35.1, 'low' => 35.5, 'normal_min' => 36.0, 'normal_max' => 37.5, 'high' => 38.5, 'critical_high' => 40.0],
        ],
        'spo2' => [
            'adult' => ['critical_low' => 88, 'low' => 90, 'normal_min' => 95, 'normal_max' => 100, 'high' => 100, 'critical_high' => 100],
            'pediatric' => ['critical_low' => 88, 'low' => 90, 'normal_min' => 95, 'normal_max' => 100, 'high' => 100, 'critical_high' => 100],
        ],
    ];

    /**
     * Normalize vital sign value to standard units.
     */
    public function normalize(float $value, string $fromUnit, string $toUnit): float
    {
        // Temperature conversions
        if (in_array($fromUnit, ['C', 'celsius']) && in_array($toUnit, ['F', 'fahrenheit'])) {
            return ($value * 9 / 5) + 32;
        }
        if (in_array($fromUnit, ['F', 'fahrenheit']) && in_array($toUnit, ['C', 'celsius'])) {
            return ($value - 32) * 5 / 9;
        }

        // Weight conversions
        if (in_array($fromUnit, ['kg', 'kilogram']) && in_array($toUnit, ['lb', 'pound'])) {
            return $value * 2.20462;
        }
        if (in_array($fromUnit, ['lb', 'pound']) && in_array($toUnit, ['kg', 'kilogram'])) {
            return $value / 2.20462;
        }

        // Height conversions
        if (in_array($fromUnit, ['cm', 'centimeter']) && in_array($toUnit, ['in', 'inch'])) {
            return $value / 2.54;
        }
        if (in_array($fromUnit, ['in', 'inch']) && in_array($toUnit, ['cm', 'centimeter'])) {
            return $value * 2.54;
        }
        if (in_array($fromUnit, ['m', 'meter']) && in_array($toUnit, ['cm', 'centimeter'])) {
            return $value * 100;
        }
        if (in_array($fromUnit, ['cm', 'centimeter']) && in_array($toUnit, ['m', 'meter'])) {
            return $value / 100;
        }

        // No conversion needed or unsupported
        return $value;
    }

    /**
     * Get reference range for a vital sign.
     */
    public function getReferenceRange(string $loincCode, int $age, string $gender = 'male'): array
    {
        $vitalType = $this->getVitalTypeFromLoinc($loincCode);

        if (! $vitalType || ! isset($this->ranges[$vitalType])) {
            return [];
        }

        $ageGroup = $age >= 18 ? 'adult' : 'pediatric';

        return $this->ranges[$vitalType][$ageGroup];
    }

    /**
     * Interpret a vital sign value.
     */
    public function interpret(float $value, string $loincCode, int $age, string $gender = 'male'): string
    {
        $range = $this->getReferenceRange($loincCode, $age, $gender);

        if (empty($range)) {
            return 'unknown';
        }

        $criticalLow = $range['critical_low'] ?? $range['low'];

        // Critical low: below the critical_low threshold
        if ($value < $criticalLow) {
            return 'critical-low';
        }
        // Low: between critical_low and normal_min
        if ($value < $range['normal_min']) {
            return 'low';
        }
        // Normal: between normal_min and normal_max
        if ($value <= $range['normal_max']) {
            return 'normal';
        }
        // High: above normal_max but not critical
        if ($value <= $range['critical_high']) {
            return 'high';
        }

        // Critical high: above critical threshold
        return 'critical-high';
    }

    /**
     * Check if value is abnormal.
     */
    public function isAbnormal(float $value, string $loincCode, int $age, string $gender = 'male'): bool
    {
        $interpretation = $this->interpret($value, $loincCode, $age, $gender);

        return ! in_array($interpretation, ['normal', 'unknown']);
    }

    /**
     * Check if value is critical.
     */
    public function isCritical(float $value, string $loincCode, int $age, string $gender = 'male'): bool
    {
        $interpretation = $this->interpret($value, $loincCode, $age, $gender);

        return in_array($interpretation, ['critical-low', 'critical-high']);
    }

    /**
     * Get vital type from LOINC code.
     */
    protected function getVitalTypeFromLoinc(string $loincCode): ?string
    {
        return array_search($loincCode, self::LOINC_CODES) ?: null;
    }

    /**
     * Get all LOINC codes.
     */
    public function getLoincCodes(): array
    {
        return self::LOINC_CODES;
    }
}
