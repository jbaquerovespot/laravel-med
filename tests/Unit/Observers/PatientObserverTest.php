<?php

namespace Tests\Unit\Observers;

use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class PatientObserverTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_logs_activity_when_patient_is_created(): void
    {
        $patient = Patient::create([
            'document_number' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'gender' => 'male',
            'birth_date' => '1990-01-15',
            'active' => true,
        ]);

        $activity = Activity::where('subject_type', Patient::class)
            ->where('subject_id', $patient->id)
            ->where('description', 'patient.created')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('12345678', $activity->properties['document_number']);
        $this->assertEquals('Juan Pérez', $activity->properties['full_name']);
    }

    #[Test]
    public function it_logs_activity_when_patient_is_updated(): void
    {
        $patient = Patient::create([
            'document_number' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'gender' => 'male',
            'birth_date' => '1990-01-15',
            'active' => true,
        ]);

        // Clear previous activity
        Activity::query()->delete();

        $patient->update([
            'first_name' => 'Carlos',
            'phone' => '3001234567',
        ]);

        $activity = Activity::where('subject_type', Patient::class)
            ->where('subject_id', $patient->id)
            ->where('description', 'patient.updated')
            ->first();

        $this->assertNotNull($activity);
        $this->assertArrayHasKey('changes', $activity->properties);
        $this->assertEquals('Carlos', $activity->properties['changes']['first_name']);
    }

    #[Test]
    public function it_logs_activity_when_patient_is_soft_deleted(): void
    {
        $patient = Patient::create([
            'document_number' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'gender' => 'male',
            'birth_date' => '1990-01-15',
            'active' => true,
        ]);

        // Clear previous activity
        Activity::query()->delete();

        $patient->delete();

        $activity = Activity::where('subject_type', Patient::class)
            ->where('subject_id', $patient->id)
            ->where('description', 'patient.deleted')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('12345678', $activity->properties['document_number']);
    }

    #[Test]
    public function it_does_not_log_update_when_only_timestamp_changes(): void
    {
        $patient = Patient::create([
            'document_number' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'gender' => 'male',
            'birth_date' => '1990-01-15',
            'active' => true,
        ]);

        // Clear previous activity
        Activity::query()->delete();

        // Touch the model (only updates timestamps)
        $patient->touch();

        $activity = Activity::where('subject_type', Patient::class)
            ->where('subject_id', $patient->id)
            ->where('description', 'patient.updated')
            ->first();

        $this->assertNull($activity);
    }

    #[Test]
    public function it_logs_activity_when_patient_is_restored(): void
    {
        $patient = Patient::create([
            'document_number' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'gender' => 'male',
            'birth_date' => '1990-01-15',
            'active' => true,
        ]);

        $patient->delete();

        // Clear previous activity
        Activity::query()->delete();

        $patient->restore();

        $activity = Activity::where('subject_type', Patient::class)
            ->where('subject_id', $patient->id)
            ->where('description', 'patient.restored')
            ->first();

        $this->assertNotNull($activity);
    }

    #[Test]
    public function activity_includes_ip_address(): void
    {
        $patient = Patient::create([
            'document_number' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'gender' => 'male',
            'birth_date' => '1990-01-15',
            'active' => true,
        ]);

        $activity = Activity::where('subject_type', Patient::class)
            ->where('subject_id', $patient->id)
            ->first();

        $this->assertArrayHasKey('ip', $activity->properties);
    }

    #[Test]
    public function activity_includes_user_agent(): void
    {
        $patient = Patient::create([
            'document_number' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'gender' => 'male',
            'birth_date' => '1990-01-15',
            'active' => true,
        ]);

        $activity = Activity::where('subject_type', Patient::class)
            ->where('subject_id', $patient->id)
            ->first();

        $this->assertArrayHasKey('user_agent', $activity->properties);
    }

    #[Test]
    public function it_logs_activity_using_factory(): void
    {
        $patient = Patient::factory()->create();

        $activity = Activity::where('subject_type', Patient::class)
            ->where('subject_id', $patient->id)
            ->where('description', 'patient.created')
            ->first();

        $this->assertNotNull($activity);
    }
}
