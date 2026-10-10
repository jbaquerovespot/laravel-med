<?php

namespace Tests\Feature\Fhir;

use Tests\TestCase;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class FhirPatientTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function testSearchPatientsWithIdentifier(): void
    {
        Patient::create([
            'document_number' => '123456789',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'male',
            'birth_date' => '1990-01-15',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/fhir+json',
        ])->getJson('/api/fhir/Patient?identifier=123456789');

        $response->assertStatus(200);
        $response->assertJsonPath('resourceType', 'Bundle');
        $response->assertJsonPath('total', 1);
    }

    public function testShowPatientReturnsFhirResource(): void
    {
        $patient = Patient::create([
            'document_number' => '987654321',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'gender' => 'female',
            'birth_date' => '1985-06-20',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/fhir+json',
        ])->getJson("/api/fhir/Patient/{$patient->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('resourceType', 'Patient');
        $response->assertJsonPath('id', (string) $patient->id);
    }

    public function testShowPatientNotFoundReturns404(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/fhir+json',
        ])->getJson('/api/fhir/Patient/99999');

        $response->assertStatus(404);
    }

    public function testStoreValidPatientReturns201(): void
    {
        $patientData = [
            'resourceType' => 'Patient',
            'identifier' => [['value' => '111222333']],
            'name' => [['family' => 'New', 'given' => ['Patient']]],
            'gender' => 'male',
            'birthDate' => '1995-03-10',
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/fhir+json',
            'Content-Type' => 'application/fhir+json',
        ])->postJson('/api/fhir/Patient', $patientData);

        $response->assertStatus(201);
        $response->assertHeader('Location');
        $response->assertJsonPath('resourceType', 'Patient');
    }

    public function testStoreInvalidPatientReturns400(): void
    {
        $patientData = ['resourceType' => 'Patient'];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/fhir+json',
            'Content-Type' => 'application/fhir+json',
        ])->postJson('/api/fhir/Patient', $patientData);

        $response->assertStatus(400);
        $response->assertJsonPath('resourceType', 'OperationOutcome');
    }
}
