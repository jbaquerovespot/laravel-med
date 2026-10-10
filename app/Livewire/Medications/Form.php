<?php

namespace App\Livewire\Medications;

use App\Models\MedicationRequest;
use App\Models\Patient;
use App\Services\Clinical\AllergyCheckService;
use App\Services\Clinical\DrugInteractionService;
use App\Services\Fhir\FhirTransformerService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    #[Locked]
    public ?int $patientId = null;

    public ?Patient $patient = null;

    // Medication search
    public string $search = '';

    public string $selectedMedication = '';

    // Prescription details
    public string $dosage = '';

    public string $frequency = '';

    public string $duration = '';

    public string $instructions = '';

    public string $quantity = '';

    // Warnings
    public array $allergyWarnings = [];

    public array $interactionWarnings = [];

    public bool $hasWarnings = false;

    // Common medications for quick selection
    public array $commonMedications = [
        ['name' => 'Acetaminofén', 'dosage' => '500mg', 'frequency' => 'cada 6 horas'],
        ['name' => 'Ibuprofeno', 'dosage' => '400mg', 'frequency' => 'cada 8 horas'],
        ['name' => 'Amoxicilina', 'dosage' => '500mg', 'frequency' => 'cada 8 horas'],
        ['name' => 'Azitromicina', 'dosage' => '500mg', 'frequency' => 'una vez al día'],
        ['name' => 'Omeprazol', 'dosage' => '20mg', 'frequency' => 'una vez al día'],
        ['name' => 'Loratadina', 'dosage' => '10mg', 'frequency' => 'una vez al día'],
        ['name' => 'Metformina', 'dosage' => '850mg', 'frequency' => 'cada 12 horas'],
        ['name' => 'Enalapril', 'dosage' => '10mg', 'frequency' => 'una vez al día'],
        ['name' => 'Losartán', 'dosage' => '50mg', 'frequency' => 'una vez al día'],
        ['name' => 'Atorvastatina', 'dosage' => '20mg', 'frequency' => 'una vez al día'],
        ['name' => 'Salbutamol (inhalador)', 'dosage' => '100mcg', 'frequency' => 'cada 4-6 horas PRN'],
        ['name' => 'Prednisona', 'dosage' => '20mg', 'frequency' => 'una vez al día'],
    ];

    public function mount(?int $patient = null): void
    {
        $this->patientId = $patient;
        $this->loadPatient();
    }

    protected function loadPatient(): void
    {
        if ($this->patientId) {
            $this->patient = Patient::with('allergyIntolerances', 'medicationRequests')->find($this->patientId);
        }
    }

    public function selectMedication(string $name, string $dosage = '', string $frequency = ''): void
    {
        $this->selectedMedication = $name;
        $this->dosage = $dosage;
        $this->frequency = $frequency;
        $this->search = '';
        $this->checkWarnings();
    }

    public function clearSelection(): void
    {
        $this->selectedMedication = '';
        $this->dosage = '';
        $this->frequency = '';
        $this->duration = '';
        $this->instructions = '';
        $this->quantity = '';
        $this->allergyWarnings = [];
        $this->interactionWarnings = [];
        $this->hasWarnings = false;
    }

    public function updatedSelectedMedication(): void
    {
        if ($this->selectedMedication) {
            $this->checkWarnings();
        }
    }

    protected function checkWarnings(): void
    {
        $this->allergyWarnings = [];
        $this->interactionWarnings = [];
        $this->hasWarnings = false;

        if (! $this->patient || ! $this->selectedMedication) {
            return;
        }

        // Check allergies
        $allergyService = app(AllergyCheckService::class);
        $allergyCheck = $allergyService->check($this->patient, $this->selectedMedication);

        if ($allergyCheck['allergic']) {
            $this->allergyWarnings[] = [
                'reaction' => $allergyCheck['reaction'] ?? 'Reacción alérgica',
                'severity' => $allergyCheck['severity'] ?? 'moderate',
            ];
            $this->hasWarnings = true;
        }

        // Check interactions with current medications
        $interactionService = app(DrugInteractionService::class);
        $currentMeds = $this->patient->medicationRequests()
            ->where('status', 'active')
            ->pluck('medication')
            ->toArray();

        if (! empty($currentMeds)) {
            $allMeds = array_merge($currentMeds, [$this->selectedMedication]);
            $interactions = $interactionService->checkInteractions($allMeds);

            foreach ($interactions as $interaction) {
                $this->interactionWarnings[] = [
                    'drugs' => $interaction['drug1'].' + '.$interaction['drug2'],
                    'severity' => $interaction['severity'],
                    'description' => $interaction['description'],
                ];
                $this->hasWarnings = true;
            }
        }
    }

    public function save(): void
    {
        $this->validate([
            'patientId' => 'required|exists:patients,id',
            'selectedMedication' => 'required',
            'dosage' => 'required',
            'frequency' => 'required',
            'duration' => 'required',
        ]);

        try {
            $medRequest = MedicationRequest::create([
                'patient_id' => $this->patientId,
                'medication' => $this->selectedMedication,
                'dosage' => $this->dosage,
                'frequency' => $this->frequency,
                'duration' => $this->duration,
                'instructions' => $this->instructions,
                'quantity' => $this->quantity,
                'status' => 'active',
            ]);

            // Generate FHIR resource
            $fhirService = app(FhirTransformerService::class);
            $fhirResource = $fhirService->transform($medRequest, 'MedicationRequest');
            $medRequest->update(['fhir_resource' => $fhirResource]);

            session()->flash('success', __('Medicamento prescrito exitosamente.'));

            $this->redirect(route('patients.show', $this->patientId), navigate: true);
        } catch (\Exception $e) {
            $this->addError('general', __('Error al prescribir el medicamento: ').$e->getMessage());
        }
    }

    public function render(): View
    {
        $filteredMedications = [];
        if (strlen($this->search) >= 2) {
            $search = strtolower($this->search);
            $filteredMedications = array_filter($this->commonMedications, function ($med) use ($search) {
                return str_contains(strtolower($med['name']), $search);
            });
        }

        return view('livewire.medications.form', [
            'filteredMedications' => $filteredMedications,
        ]);
    }
}
