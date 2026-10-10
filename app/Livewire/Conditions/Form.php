<?php

namespace App\Livewire\Conditions;

use App\Models\Condition;
use App\Models\Patient;
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

    public string $selectedCode = '';

    public string $selectedDisplay = '';

    public string $category = 'principal';

    public string $clinicalStatus = 'active';

    public string $verificationStatus = 'confirmed';

    public string $onsetDate = '';

    public string $note = '';

    public array $cie10Codes = [];

    public function mount(?int $patient = null): void
    {
        $this->patientId = $patient;
        $this->loadPatient();
        $this->loadCie10Codes();
        $this->onsetDate = now()->format('Y-m-d');
    }

    protected function loadPatient(): void
    {
        if ($this->patientId) {
            $this->patient = Patient::find($this->patientId);
        }
    }

    protected function loadCie10Codes(): void
    {
        $path = database_path('data/cie10_codes.json');
        if (File::exists($path)) {
            $this->cie10Codes = json_decode(File::get($path), true);
        }
    }

    public function searchCodes(): void
    {
        if (strlen($this->search) < 2) {
            $this->results = [];

            return;
        }

        $search = strtoupper($this->search);
        $this->results = array_filter($this->cie10Codes, function ($code) use ($search) {
            return str_contains($code['code'], $search) ||
                   str_contains(strtoupper($code['display']), $search);
        });

        $this->results = array_slice($this->results, 0, 10);
    }

    public function selectCode(string $code, string $display): void
    {
        $this->selectedCode = $code;
        $this->selectedDisplay = $display;
        $this->search = '';
        $this->results = [];
    }

    public function clearSelection(): void
    {
        $this->selectedCode = '';
        $this->selectedDisplay = '';
    }

    public function save(): void
    {
        $this->validate([
            'patientId' => 'required|exists:patients,id',
            'selectedCode' => 'required',
            'category' => 'required|in:principal,secondary,admission,discharge',
            'clinicalStatus' => 'required|in:active,recurrence,relapse,inactive,remission,resolved',
            'verificationStatus' => 'required|in:unconfirmed,provisional,differential,confirmed,refuted,entered-in-error',
        ]);

        try {
            $condition = Condition::create([
                'patient_id' => $this->patientId,
                'code' => $this->selectedCode,
                'note' => $this->selectedDisplay."\n".$this->note,
                'clinical_status' => $this->clinicalStatus,
                'verification_status' => $this->verificationStatus,
                'category' => $this->category,
                'onset_date' => $this->onsetDate ?: null,
            ]);

            // Generate FHIR resource
            $fhirService = app(FhirTransformerService::class);
            $fhirResource = $fhirService->transform($condition, 'Condition');
            $condition->update(['fhir_resource' => $fhirResource]);

            session()->flash('success', __('Diagnóstico registrado exitosamente.'));

            $this->redirect(route('patients.show', $this->patientId), navigate: true);
        } catch (\Exception $e) {
            $this->addError('general', __('Error al registrar el diagnóstico: ').$e->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.conditions.form');
    }
}
