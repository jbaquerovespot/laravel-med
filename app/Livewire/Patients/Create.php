<?php

namespace App\Livewire\Patients;

use App\Models\Patient;
use App\Rules\ValidColombianDocument;
use App\Services\Fhir\FhirTransformerService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    // Required fields
    public string $document_number = '';

    public string $first_name = '';

    public string $last_name = '';

    public string $birth_date = '';

    public string $gender = '';

    // Optional fields
    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public string $city = '';

    public string $state = '';

    public string $postal_code = '';

    public string $country = 'Colombia';

    public string $marital_status = '';

    // Emergency contact
    public string $emergency_contact_name = '';

    public string $emergency_contact_phone = '';

    // Insurance info
    public string $insurance_company = '';

    public string $insurance_number = '';

    // Blood type
    public string $blood_type = '';

    public string $rh_factor = '';

    protected function rules(): array
    {
        return [
            'document_number' => ['required', 'string', 'max:20', new ValidColombianDocument, 'unique:patients,document_number'],
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
            'insurance_company' => ['nullable', 'string', 'max:200'],
            'insurance_number' => ['nullable', 'string', 'max:50'],
            'blood_type' => ['nullable', 'in:A,B,AB,O'],
            'rh_factor' => ['nullable', 'in:+,-,positive,negative'],
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
        'address' => 'dirección',
        'city' => 'ciudad',
        'emergency_contact_name' => 'nombre del contacto de emergencia',
        'emergency_contact_phone' => 'teléfono del contacto de emergencia',
    ];

    public function save(): void
    {
        $this->validate();

        try {
            DB::transaction(function () {
                // Create patient
                $patient = Patient::create([
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
                    'active' => true,
                ]);

                // Generate FHIR resource
                $fhirService = app(FhirTransformerService::class);
                $fhirResource = $fhirService->transform($patient, 'Patient');
                $patient->update(['fhir_resource' => $fhirResource]);
            });

            session()->flash('success', __('Paciente creado exitosamente.'));
            $this->redirect(route('patients.index'), navigate: true);
        } catch (\Exception $e) {
            $this->addError('general', __('Error al crear el paciente. Por favor intente nuevamente.'));
        }
    }

    public function render(): View
    {
        return view('livewire.patients.create');
    }
}
