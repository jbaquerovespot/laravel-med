<?php

namespace Tests\Unit\Fhir\Transformers;

use App\Models\Patient;
use App\Services\Fhir\Transformers\PatientToFhirTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientToFhirTransformerTest extends TestCase
{
    use RefreshDatabase;

    protected PatientToFhirTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new PatientToFhirTransformer;
    }

    public function test_transform_patient_with_all_fields(): void
    {
        $patient = Patient::create([
            'document_number' => '123456789',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'male',
            'birth_date' => '1990-01-15',
            'email' => 'john@example.com',
            'phone' => '+573001234567',
            'address' => 'Calle 123 #45-67',
            'city' => 'Bogotá',
            'country' => 'Colombia',
            'active' => true,
        ]);

        $fhirResource = $this->transformer->transform($patient);

        $this->assertEquals('Patient', $fhirResource['resourceType']);
        $this->assertEquals((string) $patient->id, $fhirResource['id']);
        $this->assertEquals('123456789', $fhirResource['identifier'][0]['value']);
        $this->assertEquals('Doe', $fhirResource['name'][0]['family']);
        $this->assertEquals('John', $fhirResource['name'][0]['given'][0]);
        $this->assertEquals('male', $fhirResource['gender']);
        $this->assertEquals('1990-01-15', $fhirResource['birthDate']);
        $this->assertTrue($fhirResource['active']);
    }

    public function test_transform_from_fhir_patient(): void
    {
        $fhirResource = [
            'resourceType' => 'Patient',
            'identifier' => [['value' => '987654321']],
            'name' => [['family' => 'Smith', 'given' => ['Jane']]],
            'gender' => 'female',
            'birthDate' => '1985-06-20',
            'telecom' => [
                ['system' => 'email', 'value' => 'jane@example.com'],
                ['system' => 'phone', 'value' => '+573009876543'],
            ],
        ];

        $patient = $this->transformer->transformFromFhir($fhirResource);

        $this->assertEquals('987654321', $patient->document_number);
        $this->assertEquals('Jane', $patient->first_name);
        $this->assertEquals('Smith', $patient->last_name);
        $this->assertEquals('female', $patient->gender);
        $this->assertEquals('1985-06-20', $patient->birth_date?->format('Y-m-d'));
        $this->assertEquals('jane@example.com', $patient->email);
        $this->assertEquals('+573009876543', $patient->phone);
    }
}
