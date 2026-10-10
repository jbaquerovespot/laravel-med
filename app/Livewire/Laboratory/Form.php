<?php

namespace App\Livewire\Laboratory;

use App\Models\Patient;
use App\Models\ServiceRequest;
use App\Services\Fhir\FhirTransformerService;
use Illuminate\Support\Facades\File;
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

    public string $search = '';

    public array $results = [];

    public array $selectedTests = [];

    public string $priority = 'routine';

    public string $clinicalNotes = '';

    public string $fasting = 'unknown';

    public array $loincCodes = [];

    public function mount(?int $patient = null): void
    {
        $this->patientId = $patient;
        $this->loadPatient();
        $this->loadLoincCodes();
    }

    protected function loadPatient(): void
    {
        if ($this->patientId) {
            $this->patient = Patient::find($this->patientId);
        }
    }

    protected function loadLoincCodes(): void
    {
        $path = database_path('data/loinc_codes.json');
        if (File::exists($path)) {
            $this->loincCodes = json_decode(File::get($path), true);
        }
    }

    public function searchTests(): void
    {
        if (strlen($this->search) < 2) {
            $this->results = [];

            return;
        }

        $search = strtoupper($this->search);
        $this->results = array_filter($this->loincCodes, function ($test) use ($search) {
            return str_contains($test['code'], $search) ||
                   str_contains(strtoupper($test['display']), $search) ||
                   str_contains(strtoupper($test['category']), $search);
        });

        $this->results = array_slice($this->results, 0, 15);
    }

    public function addTest(string $code, string $display, string $category, string $unit): void
    {
        // Check if already added
        foreach ($this->selectedTests as $test) {
            if ($test['code'] === $code) {
                return;
            }
        }

        $this->selectedTests[] = [
            'code' => $code,
            'display' => $display,
            'category' => $category,
            'unit' => $unit,
        ];

        $this->search = '';
        $this->results = [];
    }

    public function removeTest(int $index): void
    {
        unset($this->selectedTests[$index]);
        $this->selectedTests = array_values($this->selectedTests);
    }

    public function addCommonProfile(string $profile): void
    {
        $profiles = [
            'hematology' => ['4544-3', '718-7', '6690-2', '777-3', '789-8', '785-6', '786-4', '787-2', '788-0'],
            'chemistry' => ['2345-7', '2160-0', '3094-0', '1920-8', '1742-6', '6768-6', '1975-2', '2885-2', '1759-0'],
            'lipid' => ['2085-9', '18262-6', '2089-1', '2571-8'],
            'thyroid' => ['10839-9', '3024-7'],
            'electrolytes' => ['2951-2', '2823-4', '2075-0', '2345-7'],
            'coagulation' => ['635-3', '5902-2', '6301-6'],
            'urinalysis' => ['20505-4'],
        ];

        if (! isset($profiles[$profile])) {
            return;
        }

        foreach ($profiles[$profile] as $code) {
            foreach ($this->loincCodes as $test) {
                if ($test['code'] === $code) {
                    $this->addTest($test['code'], $test['display'], $test['category'], $test['unit'] ?? '');
                    break;
                }
            }
        }
    }

    public function clearTests(): void
    {
        $this->selectedTests = [];
    }

    public function save(): void
    {
        $this->validate([
            'patientId' => 'required|exists:patients,id',
            'selectedTests' => 'required|array|min:1',
            'priority' => 'required|in:routine,urgent,stat',
        ]);

        try {
            $fhirService = app(FhirTransformerService::class);

            foreach ($this->selectedTests as $test) {
                $serviceRequest = ServiceRequest::create([
                    'patient_id' => $this->patientId,
                    'code' => $test['code'],
                    'display' => $test['display'],
                    'category' => 'laboratory',
                    'priority' => $this->priority,
                    'status' => 'active',
                    'intent' => 'order',
                    'note' => $this->clinicalNotes,
                    'authored_on' => now(),
                ]);

                // Generate FHIR resource
                $fhirResource = $fhirService->transform($serviceRequest, 'ServiceRequest');
                $serviceRequest->update(['fhir_resource' => $fhirResource]);
            }

            session()->flash('success', __('Orden de laboratorio creada exitosamente.'));

            $this->redirect(route('patients.show', $this->patientId), navigate: true);
        } catch (\Exception $e) {
            $this->addError('general', __('Error al crear la orden: ').$e->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.laboratory.form');
    }
}
