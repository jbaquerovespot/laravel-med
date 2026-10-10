<?php

namespace App\Livewire\Encounters;

use App\Models\Encounter;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    #[Locked]
    public int $encounterId;

    public ?Encounter $encounter = null;

    public function mount(int $id): void
    {
        $this->encounterId = $id;
        $this->loadEncounter();
    }

    protected function loadEncounter(): void
    {
        $this->encounter = Encounter::with([
            'patient',
            'observations' => fn ($q) => $q->orderBy('created_at'),
            'conditions' => fn ($q) => $q->orderBy('created_at'),
            'medicationRequests' => fn ($q) => $q->orderBy('created_at'),
            'serviceRequests' => fn ($q) => $q->orderBy('created_at'),
        ])->findOrFail($this->encounterId);
    }

    public function render(): View
    {
        return view('livewire.encounters.show', [
            'encounter' => $this->encounter,
        ]);
    }
}
