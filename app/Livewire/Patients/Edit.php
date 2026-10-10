<?php

namespace App\Livewire\Patients;

use App\Models\Patient;
use App\Rules\ValidColombianDocument;
use App\Services\Fhir\FhirTransformerService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app')]
class Edit extends Component
{
    #[Locked]
    public int $patientId;

    public ?Patient $patient = null;

    // Form fields
    public string $document_number = '';

    public string $first_name = '';

    public string $last_name = '';

    public string $birth_date = '';

    public string $gender = '';

    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public string $city = '';

    public string $state = '';

    public string $postal_code = '';

    public string $country = 'Colombia';

    public string $marital_status = '';

    public string $emergency_contact_name = '';

    public string $emergency_contact_phone = '';

    public string $insurance_company = '';

    public string $insurance_number = '';

    public string $blood_type = '';

    public string $rh_factor = '';

    public function mount(int $id): void
    {
        $this->patientId = $id;
        $this->loadPatient();
        $this->fillForm();
    }

    protected function loadPatient(): void
    {
        $this->patient = Patient::findOrFail($this->patientId);
    }

    protected function fillForm(): void
    {
        $this->document_number = $this->patient->document_number;
        $this->first_name = $this->patient->first_name;
        $this->last_name = $this->patient->last_name;
        $this->birth_date = $this->patient->birth_date?->format('Y-m-d') ?? '';
        $this->gender = $this->patient->gender ?? '';
        $this->email = $this->patient->email ?? '';
        $this->phone = $this->patient->phone ?? '';
        $this->address = $this->patient->address ?? '';
        $this->city = $this->patient->city ?? '';
        $this->state = $this->patient->state ?? '';
        $this->postal_code = $this->patient->postal_code ?? '';
        $this->country = $this->patient->country ?? 'Colombia';
        $this->marital_status = $this->patient->marital_status ?? '';
        $this->emergency_contact_name = $this->patient->emergency_contact_name ?? '';
        $this->emergency_contact_phone = $this->patient->emergency_contact_phone ?? '';
    }

    protected function rules(): array
    {
        return [
            'document_number' => ['required', 'string', 'max:20', new ValidColombianDocument, 'unique:patients,document_number,'.$this->patientId],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'in:male,female,other,unknown'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'marital_status' => ['nullable', 'in:single,married,divorced,widowed,unknown'],
            'emergency_contact_name' => ['nullable', 'string', 'max:200'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    protected $validationAttributes = [
        'document_number' => 'número de documento',
        'first_name' => 'primer nombre',
        'last_name' => 'apellidos',
        'birth_date' => 'fecha de nacimiento',
        'gender' => 'género',
        'email' => 'correo electrónico',
        'phone' => 'teléfono',
    ];

    public function save(): void
    {
        $this->validate();

        try {
            DB::transaction(function () {
                $this->patient->update([
                    'document_number' => $this->document_number,
                    'first_name' => $this->first_name,
                    'last_name' => $this->last_name,
                    'birth_date' => $this->birth_date,
                    'gender' => $this->gender,
                    'email' => $this->email ?: null,
                    'phone' => $this->phone ?: null,
                    'address' => $this->address ?: null,
                    'city' => $this->city ?: null,
                    'state' => $this->state ?: null,
                    'postal_code' => $this->postal_code ?: null,
                    'country' => $this->country ?: 'Colombia',
                    'marital_status' => $this->marital_status ?: null,
                    'emergency_contact_name' => $this->emergency_contact_name ?: null,
                    'emergency_contact_phone' => $this->emergency_contact_phone ?: null,
                ]);

                // Regenerate FHIR resource
                $fhirService = app(FhirTransformerService::class);
                $fhirResource = $fhirService->transform($this->patient, 'Patient');
                $this->patient->update(['fhir_resource' => $fhirResource]);
            });

            session()->flash('success', __('Paciente actualizado exitosamente.'));
            $this->redirect(route('patients.show', $this->patientId), navigate: true);
        } catch (\Exception $e) {
            $this->addError('general', __('Error al actualizar el paciente.'));
        }
    }

    public function render(): View
    {
        return view('livewire.patients.edit');
    }
}
