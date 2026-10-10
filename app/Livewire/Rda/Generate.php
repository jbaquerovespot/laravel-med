<?php

namespace App\Livewire\Rda;

use App\Models\Encounter;
use App\Services\Rda\RdaGeneratorService;
use Livewire\Component;

class Generate extends Component
{
    public int $encounterId;

    public function mount(int $encounter): void
    {
        $this->encounterId = $encounter;
    }

    public function render()
    {
        $encounter = Encounter::with('patient')->findOrFail($this->encounterId);

        if (! $encounter->patient) {
            abort(404, 'Paciente no encontrado');
        }

        $rdaService = app(RdaGeneratorService::class);

        return $rdaService->downloadRda($encounter->patient->id, $this->encounterId);
    }
}
