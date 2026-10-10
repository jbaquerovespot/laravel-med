<?php

namespace App\Livewire\Dashboard;

use App\Models\Encounter;
use App\Models\Patient;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public int $recentPatientsCount = 0;

    public int $todayEncountersCount = 0;

    public int $activePatientsCount = 0;

    public function mount(): void
    {
        $this->loadStats();
    }

    protected function loadStats(): void
    {
        $this->recentPatientsCount = Patient::where('created_at', '>=', now()->subDays(7))->count();
        $this->todayEncountersCount = Encounter::whereDate('created_at', today())->count();
        $this->activePatientsCount = Patient::where('active', true)->count();
    }

    public function render(): View
    {
        $recentPatients = Patient::orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentEncounters = Encounter::with('patient')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('livewire.dashboard.index', [
            'recentPatients' => $recentPatients,
            'recentEncounters' => $recentEncounters,
        ]);
    }
}
