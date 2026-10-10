<?php

namespace Tests\Unit\Services;

use App\Services\Clinical\ReferenceRangeService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReferenceRangeServiceTest extends TestCase
{
    private ReferenceRangeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReferenceRangeService;
    }

    #[Test]
    public function it_returns_reference_range_for_adult_male_bp(): void
    {
        $range = $this->service->getReferenceRange('8480-6', 30, 'male'); // Systolic BP

        $this->assertIsArray($range);
        $this->assertArrayHasKey('low', $range);
        $this->assertArrayHasKey('normal_min', $range);
        $this->assertArrayHasKey('normal_max', $range);
        $this->assertArrayHasKey('high', $range);
        $this->assertArrayHasKey('critical_high', $range);

        // Adult systolic BP: 90-120 is normal
        $this->assertEquals(90, $range['normal_min']);
        $this->assertEquals(120, $range['normal_max']);
    }

    #[Test]
    public function it_returns_reference_range_for_pediatric_bp(): void
    {
        $range = $this->service->getReferenceRange('8480-6', 10, 'male'); // Systolic BP

        // Pediatric systolic BP: 80-110 is normal
        $this->assertEquals(80, $range['normal_min']);
        $this->assertEquals(110, $range['normal_max']);
    }

    #[Test]
    #[DataProvider('adultBpInterpretationProvider')]
    public function it_interprets_adult_systolic_bp_correctly(float $value, string $expected): void
    {
        $interpretation = $this->service->interpret($value, '8480-6', 30, 'male');

        $this->assertEquals($expected, $interpretation);
    }

    public static function adultBpInterpretationProvider(): array
    {
        return [
            'critical low' => [80, 'critical-low'],
            'low' => [85, 'low'],
            'normal low' => [95, 'normal'],
            'normal mid' => [110, 'normal'],
            'normal high' => [120, 'normal'],
            'elevated' => [125, 'high'],
            'high stage 1' => [135, 'high'],
            'critical high' => [185, 'critical-high'],
        ];
    }

    #[Test]
    #[DataProvider('pediatricBpInterpretationProvider')]
    public function it_interprets_pediatric_systolic_bp_correctly(float $value, string $expected): void
    {
        $interpretation = $this->service->interpret($value, '8480-6', 10, 'male');

        $this->assertEquals($expected, $interpretation);
    }

    public static function pediatricBpInterpretationProvider(): array
    {
        return [
            'critical low' => [60, 'critical-low'],
            'low' => [75, 'low'],
            'normal' => [95, 'normal'],
            'high' => [115, 'high'],
            'critical high' => [145, 'critical-high'],
        ];
    }

    #[Test]
    #[DataProvider('heartRateInterpretationProvider')]
    public function it_interprets_adult_heart_rate_correctly(float $value, string $expected): void
    {
        $interpretation = $this->service->interpret($value, '8867-4', 30, 'male');

        $this->assertEquals($expected, $interpretation);
    }

    public static function heartRateInterpretationProvider(): array
    {
        return [
            'critical low (bradycardia)' => [45, 'critical-low'],
            'low' => [55, 'low'],
            'normal' => [72, 'normal'],
            'high' => [110, 'high'],
            'critical high (tachycardia)' => [160, 'critical-high'],
        ];
    }

    #[Test]
    #[DataProvider('temperatureInterpretationProvider')]
    public function it_interprets_temperature_correctly(float $value, string $expected): void
    {
        $interpretation = $this->service->interpret($value, '8310-5', 30, 'male');

        $this->assertEquals($expected, $interpretation);
    }

    public static function temperatureInterpretationProvider(): array
    {
        return [
            'critical low (hypothermia)' => [35.0, 'critical-low'],
            'low' => [35.8, 'low'],
            'normal' => [36.5, 'normal'],
            'fever' => [38.0, 'high'],
            'critical high (hyperthermia)' => [41.0, 'critical-high'],
        ];
    }

    #[Test]
    #[DataProvider('spo2InterpretationProvider')]
    public function it_interprets_spo2_correctly(float $value, string $expected): void
    {
        $interpretation = $this->service->interpret($value, '59408-5', 30, 'male');

        $this->assertEquals($expected, $interpretation);
    }

    public static function spo2InterpretationProvider(): array
    {
        return [
            'critical low (hypoxemia)' => [85, 'critical-low'],
            'low' => [92, 'low'],
            'normal' => [98, 'normal'],
        ];
    }

    #[Test]
    public function it_returns_empty_array_for_unknown_loinc_code(): void
    {
        $range = $this->service->getReferenceRange('UNKNOWN-CODE', 30, 'male');

        $this->assertEmpty($range);
    }

    #[Test]
    public function it_returns_unknown_for_unknown_loinc_code_interpretation(): void
    {
        $interpretation = $this->service->interpret(100, 'UNKNOWN-CODE', 30, 'male');

        $this->assertEquals('unknown', $interpretation);
    }

    #[Test]
    public function it_detects_abnormal_values(): void
    {
        // Normal BP
        $this->assertFalse($this->service->isAbnormal(110, '8480-6', 30, 'male'));

        // High BP
        $this->assertTrue($this->service->isAbnormal(145, '8480-6', 30, 'male'));

        // Low BP
        $this->assertTrue($this->service->isAbnormal(85, '8480-6', 30, 'male'));
    }

    #[Test]
    public function it_detects_critical_values(): void
    {
        // Normal BP
        $this->assertFalse($this->service->isCritical(110, '8480-6', 30, 'male'));

        // High BP (not critical)
        $this->assertFalse($this->service->isCritical(145, '8480-6', 30, 'male'));

        // Critical high BP
        $this->assertTrue($this->service->isCritical(185, '8480-6', 30, 'male'));

        // Critical low BP
        $this->assertTrue($this->service->isCritical(80, '8480-6', 30, 'male'));
    }

    #[Test]
    public function it_returns_loinc_codes(): void
    {
        $codes = $this->service->getLoincCodes();

        $this->assertIsArray($codes);
        $this->assertArrayHasKey('systolic_bp', $codes);
        $this->assertArrayHasKey('diastolic_bp', $codes);
        $this->assertArrayHasKey('heart_rate', $codes);
        $this->assertEquals('8480-6', $codes['systolic_bp']);
    }

    #[Test]
    public function it_uses_same_ranges_for_male_and_female(): void
    {
        $maleRange = $this->service->getReferenceRange('8480-6', 30, 'male');
        $femaleRange = $this->service->getReferenceRange('8480-6', 30, 'female');

        $this->assertEquals($maleRange, $femaleRange);
    }
}
