<?php

namespace Tests\Unit\Services;

use App\Services\Clinical\DrugInteractionService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DrugInteractionServiceTest extends TestCase
{
    private DrugInteractionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DrugInteractionService;
    }

    #[Test]
    public function it_detects_warfarin_nsaid_interaction(): void
    {
        $interactions = $this->service->checkInteractions(['warfarin', 'ibuprofen']);

        $this->assertCount(1, $interactions);
        $this->assertEquals('major', $interactions[0]['severity']);
        $this->assertStringContainsString('bleeding', strtolower($interactions[0]['description']));
    }

    #[Test]
    public function it_detects_warfarin_aspirin_interaction(): void
    {
        $interaction = $this->service->getInteraction('warfarin', 'aspirin');

        $this->assertNotNull($interaction);
        $this->assertEquals('major', $interaction['severity']);
    }

    #[Test]
    public function it_detects_serbotonin_syndrome_risk(): void
    {
        $interaction = $this->service->getInteraction('fluoxetine', 'tramadol');

        $this->assertNotNull($interaction);
        $this->assertEquals('major', $interaction['severity']);
        $this->assertStringContainsString('serotonin', strtolower($interaction['description']));
    }

    #[Test]
    public function it_detects_ssri_maoi_contraindication(): void
    {
        $interaction = $this->service->getInteraction('sertraline', 'maoi');

        $this->assertNotNull($interaction);
        $this->assertEquals('contraindicated', $interaction['severity']);
    }

    #[Test]
    public function it_detects_statin_antibiotic_interaction(): void
    {
        $interaction = $this->service->getInteraction('clarithromycin', 'statin');

        $this->assertNotNull($interaction);
        $this->assertEquals('major', $interaction['severity']);
        $this->assertStringContainsString('myopathy', strtolower($interaction['description']));
    }

    #[Test]
    public function it_detects_metformin_contrast_interaction(): void
    {
        $interaction = $this->service->getInteraction('metformin', 'contrast');

        $this->assertNotNull($interaction);
        $this->assertEquals('major', $interaction['severity']);
        $this->assertStringContainsString('lactic acidosis', strtolower($interaction['description']));
    }

    #[Test]
    public function it_detects_ace_potassium_interaction(): void
    {
        $interaction = $this->service->getInteraction('lisinopril', 'potassium');

        $this->assertNotNull($interaction);
        $this->assertEquals('moderate', $interaction['severity']);
        $this->assertStringContainsString('hyperkalemia', strtolower($interaction['description']));
    }

    #[Test]
    public function it_returns_no_interaction_for_compatible_drugs(): void
    {
        $interaction = $this->service->getInteraction('acetaminophen', 'vitamin-c');

        $this->assertNull($interaction);
    }

    #[Test]
    public function it_returns_empty_array_for_no_interactions(): void
    {
        $interactions = $this->service->checkInteractions(['acetaminophen', 'vitamin-c', 'omeprazole']);

        $this->assertEmpty($interactions);
    }

    #[Test]
    public function it_detects_multiple_interactions(): void
    {
        $interactions = $this->service->checkInteractions([
            'warfarin',
            'ibuprofen',
            'amiodarone',
        ]);

        // Warfarin + Ibuprofen and Warfarin + Amiodarone
        $this->assertCount(2, $interactions);
    }

    #[Test]
    public function it_normalizes_drug_names(): void
    {
        // Ibuprofen is mapped to NSAID class
        $interaction = $this->service->getInteraction('Warfarin', 'IBUPROFEN');

        $this->assertNotNull($interaction);
        $this->assertEquals('major', $interaction['severity']);
    }

    #[Test]
    public function it_detects_class_level_interactions(): void
    {
        // Lisinopril is an ACE inhibitor
        $interaction = $this->service->getInteraction('lisinopril', 'spironolactone');

        $this->assertNotNull($interaction);
        $this->assertStringContainsString('hyperkalemia', strtolower($interaction['description']));
    }

    #[Test]
    public function it_includes_recommendation(): void
    {
        $interaction = $this->service->getInteraction('warfarin', 'ibuprofen');

        $this->assertArrayHasKey('recommendation', $interaction);
        $this->assertNotEmpty($interaction['recommendation']);
    }

    #[Test]
    public function it_returns_severity_level_correctly(): void
    {
        $this->assertEquals(4, $this->service->getSeverityLevel('contraindicated'));
        $this->assertEquals(3, $this->service->getSeverityLevel('major'));
        $this->assertEquals(2, $this->service->getSeverityLevel('moderate'));
        $this->assertEquals(1, $this->service->getSeverityLevel('minor'));
        $this->assertEquals(0, $this->service->getSeverityLevel('unknown'));
    }

    #[Test]
    public function it_detects_opioid_benzodiazepine_interaction(): void
    {
        $interaction = $this->service->getInteraction('morphine', 'benzodiazepine');

        $this->assertNotNull($interaction);
        $this->assertEquals('major', $interaction['severity']);
        $this->assertStringContainsString('respiratory', strtolower($interaction['description']));
    }

    #[Test]
    public function it_detects_lithium_thiazide_interaction(): void
    {
        $interaction = $this->service->getInteraction('hydrochlorothiazide', 'lithium');

        $this->assertNotNull($interaction);
        $this->assertEquals('major', $interaction['severity']);
        $this->assertStringContainsString('toxicity', strtolower($interaction['description']));
    }

    #[Test]
    public function it_handles_single_medication(): void
    {
        $interactions = $this->service->checkInteractions(['warfarin']);

        $this->assertEmpty($interactions);
    }

    #[Test]
    public function it_handles_empty_medication_list(): void
    {
        $interactions = $this->service->checkInteractions([]);

        $this->assertEmpty($interactions);
    }
}
