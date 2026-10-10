<?php

namespace Tests\Unit\Fhir;

use Tests\TestCase;
use App\Services\Fhir\FhirValidatorService;

class FhirValidatorServiceTest extends TestCase
{
    protected FhirValidatorService $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new FhirValidatorService();
    }

    public function testValidateStructureMissingRequiredFields(): void
    {
        $resource = ['resourceType' => 'Patient'];
        $result = $this->validator->validate($resource, 'Patient');

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('identifier', $result->getErrorMessages()[0]);
    }

    public function testValidateStructureValidResource(): void
    {
        $resource = [
            'resourceType' => 'Patient',
            'identifier' => [['value' => '123456789']],
            'name' => [['family' => 'Test']],
            'gender' => 'male',
            'birthDate' => '1990-01-15',
        ];

        $result = $this->validator->validate($resource, 'Patient');

        $this->assertTrue($result->isValid());
    }

    public function testValidateBusinessRulesInvalidCedula(): void
    {
        $resource = [
            'resourceType' => 'Patient',
            'identifier' => [['value' => 'invalid']],
            'name' => [['family' => 'Test']],
            'gender' => 'male',
            'birthDate' => '1990-01-15',
        ];

        $result = $this->validator->validate($resource, 'Patient');

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('document number', $result->getErrorMessages()[0]);
    }

    public function testValidateBusinessRulesFutureBirthDate(): void
    {
        $resource = [
            'resourceType' => 'Patient',
            'identifier' => [['value' => '123456789']],
            'name' => [['family' => 'Test']],
            'gender' => 'male',
            'birthDate' => '2030-01-15',
        ];

        $result = $this->validator->validate($resource, 'Patient');

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('future', $result->getErrorMessages()[0]);
    }
}
