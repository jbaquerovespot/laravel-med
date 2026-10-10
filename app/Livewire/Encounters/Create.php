<?php

namespace App\Livewire\Encounters;

use App\Models\Condition;
use App\Models\Encounter;
use App\Models\MedicationRequest;
use App\Models\Observation;
use App\Models\Patient;
use App\Services\Clinical\AllergyCheckService;
use App\Services\Clinical\DrugInteractionService;
use App\Services\Fhir\FhirTransformerService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    #[Locked]
    public ?int $patientId = null;

    public ?Patient $patient = null;

    public int $currentStep = 1;

    public int $totalSteps = 5;

    // Step 1: Patient Selection
    public string $patientSearch = '';

    public Collection $patientResults;

    // Step 2: Reason for Visit
    public string $chiefComplaint = '';

    public string $anamnesis = '';

    public string $relatedHistory = '';

    // Step 3: Vital Signs
    public string $systolicBp = '';

    public string $diastolicBp = '';

    public string $heartRate = '';

    public string $respiratoryRate = '';

    public string $temperature = '';

    public string $spo2 = '';

    public string $weight = '';

    public string $height = '';

    public ?float $bmi = null;

    // Step 4: Diagnosis
    public array $diagnoses = [];

    public string $diagnosisSearch = '';

    public string $selectedDiagnosisCode = '';

    public string $selectedDiagnosisDisplay = '';

    public string $diagnosisType = 'principal';

    // Step 5: Treatment
    public array $medications = [];

    public string $medicationSearch = '';

    public string $selectedMedication = '';

    public string $dosage = '';

    public string $frequency = '';

    public string $duration = '';

    public string $instructions = '';

    public array $allergyWarnings = [];

    public array $interactionWarnings = [];

    // Encounter ID after creation
    public ?int $encounterId = null;

    protected $listeners = [
        'patientSelected' => 'selectPatient',
        'diagnosisAdded' => 'addDiagnosis',
        'medicationAdded' => 'addMedication',
    ];

    public function mount(?int $patient = null): void
    {
        $this->patientResults = new Collection;

        if ($patient) {
            $this->patientId = $patient;
            $this->loadPatient();
        }
    }

    /**
     * Hook que se ejecuta cuando cambia patientSearch.
     */
    public function updatedPatientSearch(): void
    {
        $this->searchPatients();
    }

    protected function loadPatient(): void
    {
        if ($this->patientId) {
            $this->patient = Patient::with('allergyIntolerances')->find($this->patientId);
        }
    }

    public function selectPatient(int $patientId): void
    {
        $this->patientId = $patientId;
        $this->loadPatient();
        $this->currentStep = 2;
    }

    public function searchPatients(): void
    {
        if (strlen($this->patientSearch) < 2) {
            $this->patientResults = new Collection;

            return;
        }

        $this->patientResults = Patient::query()
            ->where(function ($q) {
                $q->where('document_number', 'ILIKE', "%{$this->patientSearch}%")
                    ->orWhere('first_name', 'ILIKE', "%{$this->patientSearch}%")
                    ->orWhere('last_name', 'ILIKE', "%{$this->patientSearch}%");
            })
            ->limit(10)
            ->get();
    }

    public function nextStep(): void
    {
        if ($this->currentStep < $this->totalSteps) {
            $this->validateCurrentStep();
            $this->currentStep++;

            if ($this->currentStep === 3) {
                $this->calculateBmi();
            }
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    protected function validateCurrentStep(): void
    {
        match ($this->currentStep) {
            1 => $this->validate(['patientId' => 'required']),
            2 => $this->validate(['chiefComplaint' => 'required|min:3']),
            default => null,
        };
    }

    public function calculateBmi(): void
    {
        if ($this->weight && $this->height) {
            $weight = (float) $this->weight;
            $height = (float) $this->height / 100; // cm to m
            $this->bmi = round($weight / ($height * $height), 1);
        }
    }

    public function addDiagnosis(): void
    {
        if ($this->selectedDiagnosisCode && $this->selectedDiagnosisDisplay) {
            $this->diagnoses[] = [
                'code' => $this->selectedDiagnosisCode,
                'display' => $this->selectedDiagnosisDisplay,
                'type' => $this->diagnosisType,
            ];

            // Reset form
            $this->selectedDiagnosisCode = '';
            $this->selectedDiagnosisDisplay = '';
            $this->diagnosisSearch = '';
        }
    }

    public function removeDiagnosis(int $index): void
    {
        unset($this->diagnoses[$index]);
        $this->diagnoses = array_values($this->diagnoses);
    }

    public function addMedication(): void
    {
        if ($this->selectedMedication) {
            // Check for allergies
            $allergyService = app(AllergyCheckService::class);
            $allergyCheck = $allergyService->check($this->patient, $this->selectedMedication);

            if ($allergyCheck['allergic']) {
                $this->allergyWarnings[] = [
                    'medication' => $this->selectedMedication,
                    'reaction' => $allergyCheck['reaction'],
                    'severity' => $allergyCheck['severity'],
                ];
            }

            // Check for interactions
            $interactionService = app(DrugInteractionService::class);
            $allMeds = array_column($this->medications, 'name');
            $allMeds[] = $this->selectedMedication;
            $interactions = $interactionService->checkInteractions($allMeds);

            foreach ($interactions as $interaction) {
                $this->interactionWarnings[] = [
                    'drugs' => $interaction['drug1'].' + '.$interaction['drug2'],
                    'severity' => $interaction['severity'],
                    'description' => $interaction['description'],
                ];
            }

            $this->medications[] = [
                'name' => $this->selectedMedication,
                'dosage' => $this->dosage,
                'frequency' => $this->frequency,
                'duration' => $this->duration,
                'instructions' => $this->instructions,
            ];

            // Reset form
            $this->selectedMedication = '';
            $this->dosage = '';
            $this->frequency = '';
            $this->duration = '';
            $this->instructions = '';
        }
    }

    public function removeMedication(int $index): void
    {
        unset($this->medications[$index]);
        $this->medications = array_values($this->medications);
    }

    public function submit(): void
    {
        $this->validate([
            'patientId' => 'required',
            'chiefComplaint' => 'required|min:3',
        ]);

        try {
            DB::transaction(function () {
                $fhirService = app(FhirTransformerService::class);

                // Create Encounter
                $encounter = Encounter::create([
                    'patient_id' => $this->patientId,
                    'status' => 'finished',
                    'class' => 'AMB',
                    'type' => 'consultation',
                    'reason' => $this->chiefComplaint,
                    'note' => $this->anamnesis,
                ]);

                $fhirResource = $fhirService->transform($encounter, 'Encounter');
                $encounter->update(['fhir_resource' => $fhirResource]);
                $this->encounterId = $encounter->id;

                // Create Observations for vital signs
                $this->createVitalSigns($encounter, $fhirService);

                // Create Conditions
                foreach ($this->diagnoses as $diagnosis) {
                    $condition = Condition::create([
                        'patient_id' => $this->patientId,
                        'encounter_id' => $encounter->id,
                        'code' => $diagnosis['code'],
                        'note' => $diagnosis['display'],
                        'clinical_status' => 'active',
                        'verification_status' => 'confirmed',
                        'category' => $diagnosis['type'] === 'principal' ? 'principal' : 'secondary',
                    ]);
                    $fhirResource = $fhirService->transform($condition, 'Condition');
                    $condition->update(['fhir_resource' => $fhirResource]);
                }

                // Create MedicationRequests
                foreach ($this->medications as $med) {
                    $medRequest = MedicationRequest::create([
                        'patient_id' => $this->patientId,
                        'encounter_id' => $encounter->id,
                        'medication' => $med['name'],
                        'dosage' => $med['dosage'],
                        'frequency' => $med['frequency'],
                        'duration' => $med['duration'],
                        'instructions' => $med['instructions'],
                        'status' => 'active',
                    ]);
                    $fhirResource = $fhirService->transform($medRequest, 'MedicationRequest');
                    $medRequest->update(['fhir_resource' => $fhirResource]);
                }
            });

            session()->flash('success', __('Consulta guardada exitosamente.'));
            $this->redirect(route('patients.show', $this->patientId), navigate: true);
        } catch (\Exception $e) {
            $this->addError('general', __('Error al guardar la consulta: ').$e->getMessage());
        }
    }

    protected function createVitalSigns(Encounter $encounter, FhirTransformerService $fhirService): void
    {
        $vitalSigns = [
            ['code' => '8480-6', 'value' => $this->systolicBp, 'unit' => 'mmHg', 'display' => 'Systolic BP'],
            ['code' => '8462-4', 'value' => $this->diastolicBp, 'unit' => 'mmHg', 'display' => 'Diastolic BP'],
            ['code' => '8867-4', 'value' => $this->heartRate, 'unit' => '/min', 'display' => 'Heart Rate'],
            ['code' => '9279-1', 'value' => $this->respiratoryRate, 'unit' => '/min', 'display' => 'Respiratory Rate'],
            ['code' => '8310-5', 'value' => $this->temperature, 'unit' => '°C', 'display' => 'Temperature'],
            ['code' => '59408-5', 'value' => $this->spo2, 'unit' => '%', 'display' => 'SpO2'],
            ['code' => '29463-7', 'value' => $this->weight, 'unit' => 'kg', 'display' => 'Weight'],
            ['code' => '8302-2', 'value' => $this->height, 'unit' => 'cm', 'display' => 'Height'],
        ];

        foreach ($vitalSigns as $vital) {
            if ($vital['value']) {
                $observation = Observation::create([
                    'patient_id' => $this->patientId,
                    'encounter_id' => $encounter->id,
                    'code' => $vital['code'],
                    'value' => (float) $vital['value'],
                    'unit' => $vital['unit'],
                    'status' => 'final',
                    'category' => 'vital-signs',
                ]);
                $fhirResource = $fhirService->transform($observation, 'Observation');
                $observation->update(['fhir_resource' => $fhirResource]);
            }
        }
    }

    public function render(): View
    {
        return view('livewire.encounters.create');
    }
}
