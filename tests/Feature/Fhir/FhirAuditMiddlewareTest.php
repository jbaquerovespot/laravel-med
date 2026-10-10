<?php

namespace Tests\Feature\Fhir;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class FhirAuditMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function testRequestIsLoggedToActivityLog(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/fhir+json',
        ])->getJson('/api/fhir/Patient');

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'FHIR API: GET /Patient',
            'causer_type' => 'App\Models\User',
            'causer_id' => $user->id,
        ]);
    }
}
