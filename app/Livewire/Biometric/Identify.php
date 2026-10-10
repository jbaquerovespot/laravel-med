<?php

namespace App\Livewire\Biometric;

use App\Models\Patient;
use App\Services\Biometric\BiometricService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Identify extends Component
{
    public bool $isCapturing = false;

    public string $status = 'ready'; // ready, capturing, success, error

    public string $statusMessage = '';

    public ?Patient $identifiedPatient = null;

    public ?int $matchScore = null;

    public bool $biometricEnabled = false;

    protected BiometricService $biometricService;

    public function mount(): void
    {
        $this->biometricService = app(BiometricService::class);
        $this->biometricEnabled = $this->biometricService->isBiometricEnabled();
    }

    public function startIdentification(): void
    {
        if (! $this->biometricEnabled) {
            $this->status = 'error';
            $this->statusMessage = __('El sistema biométrico no está habilitado.');

            return;
        }

        $this->isCapturing = true;
        $this->status = 'capturing';
        $this->statusMessage = __('Coloque el dedo en el lector para identificación...');
        $this->identifiedPatient = null;
        $this->matchScore = null;

        // Initialize device
        $initResult = $this->biometricService->initializeDevice();
        if (! $initResult['success']) {
            $this->status = 'error';
            $this->statusMessage = $initResult['error'];
            $this->isCapturing = false;

            return;
        }

        // Capture fingerprint
        $captureResult = $this->biometricService->captureFingerprint();

        if (! $captureResult['success']) {
            $this->status = 'error';
            $this->statusMessage = $captureResult['error'];
            $this->isCapturing = false;

            return;
        }

        // Authenticate
        $authResult = $this->biometricService->authenticateByFingerprint($captureResult['template']);

        $this->isCapturing = false;

        if ($authResult['success']) {
            $this->status = 'success';
            $this->identifiedPatient = $authResult['patient'];
            $this->matchScore = $authResult['match_score'];
            $this->statusMessage = __('Paciente identificado exitosamente.');
        } else {
            $this->status = 'error';
            $this->statusMessage = $authResult['error'];
        }
    }

    public function goToPatient(): void
    {
        if ($this->identifiedPatient) {
            $this->redirect(route('patients.show', $this->identifiedPatient->id), navigate: true);
        }
    }

    public function newIdentification(): void
    {
        $this->status = 'ready';
        $this->statusMessage = '';
        $this->identifiedPatient = null;
        $this->matchScore = null;
    }

    public function render(): View
    {
        return view('livewire.biometric.identify');
    }
}
