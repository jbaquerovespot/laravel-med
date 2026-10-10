<?php

namespace App\Livewire\Patients;

use App\Models\Patient;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    #[Locked]
    public int $patientId;

    public ?Patient $patient = null;

    public function mount(int $id): void
    {
        $this->patientId = $id;
        $this->loadPatient();
    }

    protected function loadPatient(): void
    {
        $this->patient = Patient::with([
            'encounters' => fn ($q) => $q->orderBy('created_at', 'desc')->limit(10),
            'conditions' => fn ($q) => $q->where('clinical_status', 'active')->orderBy('created_at', 'desc'),
            'medicationRequests' => fn ($q) => $q->where('status', 'active')->orderBy('created_at', 'desc'),
            'allergyIntolerances' => fn ($q) => $q->where('clinical_status', 'active')->orderBy('created_at', 'desc'),
        ])->findOrFail($this->patientId);
    }

    public function render(): View
    {
        return view('livewire.patients.show', [
            'patient' => $this->patient,
            'encounters' => $this->patient->encounters,
            'conditions' => $this->patient->conditions,
            'medications' => $this->patient->medicationRequests,
            'allergies' => $this->patient->allergyIntolerances,
        ]);
    }
}
