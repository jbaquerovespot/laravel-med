<?php

namespace App\Livewire\Patients;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public int $perPage = 20;

    protected string $paginationTheme = 'tailwind';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $patients = Patient::query()
            ->when($this->search, function (Builder $query) {
                $search = $this->search;
                $query->where(function (Builder $q) use ($search) {
                    $q->where('document_number', 'ILIKE', "%{$search}%")
                        ->orWhere('first_name', 'ILIKE', "%{$search}%")
                        ->orWhere('last_name', 'ILIKE', "%{$search}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) ILIKE ?", ["%{$search}%"]);
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.patients.index', [
            'patients' => $patients,
        ]);
    }
}
