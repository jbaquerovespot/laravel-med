<?php

namespace App\Livewire\Biometric;

use App\Models\Patient;
use App\Models\PatientFingerprint;
use App\Services\Biometric\BiometricService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app')]
class Enroll extends Component
{
    #[Locked]
    public ?int $patientId = null;

    public ?Patient $patient = null;

    public string $selectedFinger = PatientFingerprint::FINGER_RIGHT_INDEX;

    public bool $isCapturing = false;

    public ?int $capturedQuality = null;

    public ?string $capturedTemplate = null;

    public string $status = 'ready'; // ready, capturing, success, error

    public string $statusMessage = '';

    public array $enrolledFingers = [];

    public bool $biometricEnabled = false;

    public array $fingerOptions = [];

    protected BiometricService $biometricService;

    public function mount(?int $patient = null): void
    {
        $this->biometricService = app(BiometricService::class);
        $this->biometricEnabled = $this->biometricService->isBiometricEnabled();
        $this->fingerOptions = PatientFingerprint::getFingerOptions();

        if ($patient) {
            $this->patientId = $patient;
            $this->loadPatient();
        }
    }

    protected function loadPatient(): void
    {
        if ($this->patientId) {
            $this->patient = Patient::find($this->patientId);
            $this->loadEnrolledFingers();
        }
    }

    protected function loadEnrolledFingers(): void
    {
        if ($this->patient) {
            $this->enrolledFingers = $this->biometricService->getEnrolledFingers($this->patient);
        }
    }

    public function selectFinger(string $finger): void
    {
        $this->selectedFinger = $finger;
        $this->resetCapture();
    }

    public function startCapture(): void
    {
        if (! $this->biometricEnabled) {
            $this->status = 'error';
            $this->statusMessage = __('El sistema biométrico no está habilitado.');

            return;
        }

        if (! $this->patient) {
            $this->status = 'error';
            $this->statusMessage = __('Debe seleccionar un paciente primero.');

            return;
        }

        $this->isCapturing = true;
        $this->status = 'capturing';
        $this->statusMessage = __('Coloque el dedo en el lector...');

        // Initialize device
        $initResult = $this->biometricService->initializeDevice();
        if (! $initResult['success']) {
            $this->status = 'error';
            $this->statusMessage = $initResult['error'];
            $this->isCapturing = false;

            return;
        }

        // Capture fingerprint
        $result = $this->biometricService->captureFingerprint();

        $this->isCapturing = false;

        if (! $result['success']) {
            $this->status = 'error';
            $this->statusMessage = $result['error'];

            return;
        }

        $this->capturedTemplate = $result['template'];
        $this->capturedQuality = $result['quality_score'];
        $this->status = 'success';

        $qualityLevel = $result['quality_score'] >= 80 ? 'excelente' :
            ($result['quality_score'] >= 60 ? 'buena' : 'regular');
        $this->statusMessage = __('Huella capturada. Calidad: :quality (:score)', [
            'quality' => $qualityLevel,
            'score' => $result['quality_score'],
        ]);
    }

    public function saveEnrollment(): void
    {
        if (! $this->capturedTemplate) {
            $this->status = 'error';
            $this->statusMessage = __('No hay huella capturada para guardar.');

            return;
        }

        $result = $this->biometricService->enrollFingerprint(
            $this->patient,
            $this->selectedFinger,
            $this->capturedTemplate,
            $this->capturedQuality
        );

        if ($result instanceof PatientFingerprint) {
            $this->loadEnrolledFingers();
            $this->resetCapture();
            session()->flash('success', __('Huella enrolada exitosamente.'));
        } else {
            $this->status = 'error';
            $this->statusMessage = $result['error'] ?? __('Error al enrolar huella.');
        }
    }

    public function deleteFingerprint(int $fingerprintId): void
    {
        $fingerprint = PatientFingerprint::find($fingerprintId);

        if (! $fingerprint || $fingerprint->patient_id !== $this->patientId) {
            $this->status = 'error';
            $this->statusMessage = __('Huella no encontrada.');

            return;
        }

        $this->biometricService->deleteFingerprint($fingerprint);
        $this->loadEnrolledFingers();
        session()->flash('success', __('Huella eliminada exitosamente.'));
    }

    public function resetCapture(): void
    {
        $this->isCapturing = false;
        $this->capturedQuality = null;
        $this->capturedTemplate = null;
        $this->status = 'ready';
        $this->statusMessage = '';
    }

    public function render(): View
    {
        return view('livewire.biometric.enroll');
    }
}
