<?php

namespace Tests\Unit\Services;

use App\Models\AllergyIntolerance;
use App\Models\Patient;
use App\Services\Clinical\AllergyCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AllergyCheckServiceTest extends TestCase
{
    use RefreshDatabase;

    private AllergyCheckService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AllergyCheckService;
    }

    #[Test]
    public function it_detects_allergy_for_exact_match(): void
    {
        $patient = Patient::factory()->create();
        AllergyIntolerance::create([
            'patient_id' => $patient->id,
            'code' => 'penicillin',
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
            'severity' => 'severe',
            'reaction' => 'Anaphylaxis',
        ]);

        $result = $this->service->check($patient, 'penicillin');

        $this->assertTrue($result['allergic']);
        $this->assertEquals('severe', $result['severity']);
        $this->assertEquals('Anaphylaxis', $result['reaction']);
    }

    #[Test]
    public function it_detects_allergy_for_partial_match(): void
    {
        $patient = Patient::factory()->create();
        AllergyIntolerance::create([
            'patient_id' => $patient->id,
            'code' => 'penicillin',
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
            'severity' => 'moderate',
        ]);

        $result = $this->service->check($patient, 'Penicillin VK');

        $this->assertTrue($result['allergic']);
    }

    #[Test]
    public function it_detects_allergy_within_drug_class(): void
    {
        $patient = Patient::factory()->create();
        AllergyIntolerance::create([
            'patient_id' => $patient->id,
            'code' => 'penicillin',
            'note' => 'Penicillin allergy',
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
            'severity' => 'severe',
        ]);

        // Amoxicillin is in the penicillin class
        $result = $this->service->check($patient, 'amoxicillin');

        $this->assertTrue($result['allergic']);
    }

    #[Test]
    public function it_detects_sulfa_allergy_with_bactrim(): void
    {
        $patient = Patient::factory()->create();
        AllergyIntolerance::create([
            'patient_id' => $patient->id,
            'code' => 'sulfa',
            'note' => 'Sulfa allergy',
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
        ]);

        $result = $this->service->check($patient, 'Bactrim');

        $this->assertTrue($result['allergic']);
    }

    #[Test]
    public function it_detects_nsaid_allergy_with_ibuprofen(): void
    {
        $patient = Patient::factory()->create();
        AllergyIntolerance::create([
            'patient_id' => $patient->id,
            'code' => 'nsaid',
            'note' => 'NSAID allergy',
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
        ]);

        $result = $this->service->check($patient, 'Ibuprofen');

        $this->assertTrue($result['allergic']);
    }

    #[Test]
    public function it_returns_no_allergy_when_not_allergic(): void
    {
        $patient = Patient::factory()->create();
        AllergyIntolerance::create([
            'patient_id' => $patient->id,
            'code' => 'penicillin',
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
        ]);

        $result = $this->service->check($patient, 'Acetaminophen');

        $this->assertFalse($result['allergic']);
        $this->assertNull($result['severity']);
        $this->assertNull($result['reaction']);
    }

    #[Test]
    public function it_returns_no_allergy_for_patient_without_allergies(): void
    {
        $patient = Patient::factory()->create();

        $result = $this->service->check($patient, 'Amoxicillin');

        $this->assertFalse($result['allergic']);
    }

    #[Test]
    public function it_ignores_inactive_allergies(): void
    {
        $patient = Patient::factory()->create();
        AllergyIntolerance::create([
            'patient_id' => $patient->id,
            'code' => 'penicillin',
            'clinical_status' => 'inactive', // Not active
            'verification_status' => 'confirmed',
        ]);

        $result = $this->service->check($patient, 'penicillin');

        $this->assertFalse($result['allergic']);
    }

    #[Test]
    public function it_gets_all_active_allergies_for_patient(): void
    {
        $patient = Patient::factory()->create();

        AllergyIntolerance::create([
            'patient_id' => $patient->id,
            'code' => 'penicillin',
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
        ]);

        AllergyIntolerance::create([
            'patient_id' => $patient->id,
            'code' => 'sulfa',
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
        ]);

        AllergyIntolerance::create([
            'patient_id' => $patient->id,
            'code' => 'inactive-allergy',
            'clinical_status' => 'inactive',
            'verification_status' => 'confirmed',
        ]);

        $allergies = $this->service->getAllergies($patient);

        $this->assertCount(2, $allergies);
    }

    #[Test]
    public function it_creates_allergy_record(): void
    {
        $patient = Patient::factory()->create();

        $allergy = $this->service->createAllergy($patient, [
            'code' => 'aspirin',
            'note' => 'Aspirin causes hives',
            'severity' => 'mild',
            'reaction' => 'Hives',
        ]);

        $this->assertDatabaseHas('allergy_intolerances', [
            'patient_id' => $patient->id,
            'code' => 'aspirin',
            'clinical_status' => 'active',
            'severity' => 'mild',
        ]);
    }

    #[Test]
    public function it_is_case_insensitive(): void
    {
        $patient = Patient::factory()->create();
        AllergyIntolerance::create([
            'patient_id' => $patient->id,
            'code' => 'PENICILLIN',
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
        ]);

        $result = $this->service->check($patient, 'penicillin');

        $this->assertTrue($result['allergic']);
    }

    #[Test]
    public function it_detects_ace_inhibitor_class_allergy(): void
    {
        $patient = Patient::factory()->create();
        AllergyIntolerance::create([
            'patient_id' => $patient->id,
            'code' => 'enalapril',
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
        ]);

        // Lisinopril is in the same ACE inhibitor class
        $result = $this->service->check($patient, 'lisinopril');

        $this->assertTrue($result['allergic']);
    }
}
