<?php

namespace Tests\Feature\Fhir;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FhirAuthTest extends TestCase
{
    use RefreshDatabase;

    public function testSearchWithoutTokenReturns401(): void
    {
        $response = $this->getJson('/api/fhir/Patient');
        $response->assertStatus(401);
        $response->assertJsonPath('resourceType', 'OperationOutcome');
        $response->assertJsonPath('issue.0.code', 'login');
    }

    public function testSearchWithInvalidTokenReturns401(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->getJson('/api/fhir/Patient');

        $response->assertStatus(401);
    }
}
