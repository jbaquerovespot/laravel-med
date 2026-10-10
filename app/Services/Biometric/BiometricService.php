<?php

namespace App\Services\Biometric;

use App\Models\Patient;
use App\Models\PatientFingerprint;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Biometric Service for fingerprint capture and authentication.
 *
 * This service provides integration stubs for biometric SDKs like:
 * - DigitalPersona (U.are.U)
 * - Suprema
 * - Futronic
 *
 * In production, implement the actual SDK integration by overriding
 * the stub methods with real device communication.
 *
 * Colombian Compliance:
 * - Ley 1581 de 2012 (Data Protection)
 * - Decreto 1377 de 2013
 * - Resolución 1995 de 1999 (Medical Records)
 */
class BiometricService
{
    /**
     * Minimum quality score for enrollment.
     */
    protected int $minQualityScore;

    /**
     * Matching threshold (0-100, higher = more strict).
     */
    protected int $matchingThreshold;

    /**
     * SDK type being used.
     */
    protected string $sdkType;

    /**
     * Device information.
     */
    protected ?array $deviceInfo = null;

    public function __construct()
    {
        $this->minQualityScore = config('hce.biometric.min_quality_score', 60);
        $this->matchingThreshold = config('hce.biometric.matching_threshold', 40);
        $this->sdkType = config('hce.biometric.sdk', 'stub');
    }

    /**
     * Initialize the biometric device.
     *
     * @return array Device information or error
     */
    public function initializeDevice(): array
    {
        try {
            // In production, call SDK initialization
            // Example: DigitalPersona SDK: DPFPInit()
            // Example: Suprema SDK: UF_Init()

            if ($this->sdkType === 'stub') {
                return $this->initializeStubDevice();
            }

            return match ($this->sdkType) {
                'digitalpersona' => $this->initializeDigitalPersona(),
                'suprema' => $this->initializeSuprema(),
                'futronic' => $this->initializeFutronic(),
                default => $this->initializeStubDevice(),
            };
        } catch (Exception $e) {
            Log::error('Biometric device initialization failed', [
                'error' => $e->getMessage(),
                'sdk' => $this->sdkType,
            ]);

            return [
                'success' => false,
                'error' => __('Error al inicializar dispositivo biométrico: ').$e->getMessage(),
            ];
        }
    }

    /**
     * Capture a fingerprint from the device.
     *
     * @return array Contains template, quality_score, and image_preview (base64)
     */
    public function captureFingerprint(): array
    {
        try {
            if (! $this->isBiometricEnabled()) {
                return [
                    'success' => false,
                    'error' => __('El sistema biométrico no está habilitado.'),
                ];
            }

            // In production, call SDK capture method
            // Example: DPFPGetFingerprint() or UF_Capture()

            if ($this->sdkType === 'stub') {
                return $this->captureStubFingerprint();
            }

            return match ($this->sdkType) {
                'digitalpersona' => $this->captureDigitalPersona(),
                'suprema' => $this->captureSuprema(),
                'futronic' => $this->captureFutronic(),
                default => $this->captureStubFingerprint(),
            };
        } catch (Exception $e) {
            Log::error('Fingerprint capture failed', [
                'error' => $e->getMessage(),
                'sdk' => $this->sdkType,
            ]);

            return [
                'success' => false,
                'error' => __('Error al capturar huella: ').$e->getMessage(),
            ];
        }
    }

    /**
     * Enroll a patient's fingerprint.
     *
     * @param  Patient  $patient  The patient to enroll
     * @param  string  $fingerIdentifier  Finger identifier (e.g., 'right_index')
     * @param  string  $template  The fingerprint template
     * @param  int  $qualityScore  Quality score from capture
     * @return PatientFingerprint|array The enrolled fingerprint or error array
     */
    public function enrollFingerprint(
        Patient $patient,
        string $fingerIdentifier,
        string $template,
        int $qualityScore
    ): PatientFingerprint|array {
        try {
            // Validate quality score
            if ($qualityScore < $this->minQualityScore) {
                return [
                    'success' => false,
                    'error' => __('Calidad de huella insuficiente. Mínimo requerido: ').$this->minQualityScore,
                ];
            }

            // Check if finger is already enrolled
            $existing = PatientFingerprint::where('patient_id', $patient->id)
                ->where('finger_identifier', $fingerIdentifier)
                ->where('is_active', true)
                ->first();

            if ($existing) {
                // Update existing enrollment
                $existing->update([
                    'encrypted_template' => encrypt($template),
                    'template_hash' => hash('sha256', $template),
                    'quality_score' => $qualityScore,
                    'device_serial' => $this->deviceInfo['serial'] ?? null,
                    'device_model' => $this->deviceInfo['model'] ?? null,
                    'capture_attempts' => $existing->capture_attempts + 1,
                ]);

                return $existing;
            }

            // Create new enrollment
            $fingerprint = PatientFingerprint::create([
                'patient_id' => $patient->id,
                'finger_identifier' => $fingerIdentifier,
                'encrypted_template' => encrypt($template),
                'template_hash' => hash('sha256', $template),
                'quality_score' => $qualityScore,
                'device_serial' => $this->deviceInfo['serial'] ?? null,
                'device_model' => $this->deviceInfo['model'] ?? null,
                'consent_date' => now(),
            ]);

            // Update patient biometric status
            $patient->update([
                'biometric_enrolled' => true,
                'biometric_enrolled_at' => now(),
            ]);

            Log::info('Patient fingerprint enrolled', [
                'patient_id' => $patient->id,
                'finger' => $fingerIdentifier,
                'quality' => $qualityScore,
            ]);

            return $fingerprint;
        } catch (Exception $e) {
            Log::error('Fingerprint enrollment failed', [
                'patient_id' => $patient->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => __('Error al enrolar huella: ').$e->getMessage(),
            ];
        }
    }

    /**
     * Authenticate a patient by fingerprint.
     *
     * @param  string  $capturedTemplate  The captured fingerprint template
     * @return array Contains patient and match_score, or error
     */
    public function authenticateByFingerprint(string $capturedTemplate): array
    {
        try {
            if (! $this->isBiometricEnabled()) {
                return [
                    'success' => false,
                    'error' => __('El sistema biométrico no está habilitado.'),
                ];
            }

            // Get all active fingerprints with acceptable quality
            $fingerprints = PatientFingerprint::active()
                ->acceptableQuality()
                ->with('patient')
                ->get();

            if ($fingerprints->isEmpty()) {
                return [
                    'success' => false,
                    'error' => __('No hay huellas enroladas en el sistema.'),
                ];
            }

            $bestMatch = null;
            $bestScore = 0;

            foreach ($fingerprints as $fingerprint) {
                $storedTemplate = $fingerprint->getTemplate();
                $matchScore = $this->compareTemplates($capturedTemplate, $storedTemplate);

                if ($matchScore >= $this->matchingThreshold && $matchScore > $bestScore) {
                    $bestMatch = $fingerprint;
                    $bestScore = $matchScore;
                }
            }

            if ($bestMatch) {
                // Record successful authentication
                $bestMatch->recordAuthentication();

                Log::info('Biometric authentication successful', [
                    'patient_id' => $bestMatch->patient_id,
                    'match_score' => $bestScore,
                ]);

                return [
                    'success' => true,
                    'patient' => $bestMatch->patient,
                    'fingerprint' => $bestMatch,
                    'match_score' => $bestScore,
                ];
            }

            return [
                'success' => false,
                'error' => __('Huella no reconocida. Intente nuevamente.'),
            ];
        } catch (Exception $e) {
            Log::error('Biometric authentication failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => __('Error en autenticación biométrica: ').$e->getMessage(),
            ];
        }
    }

    /**
     * Compare two fingerprint templates.
     *
     * @return int Match score (0-100)
     */
    protected function compareTemplates(string $template1, string $template2): int
    {
        // In production, use SDK comparison method
        // Example: DPFPVerifyMatch() or UF_Match()

        if ($this->sdkType === 'stub') {
            return $this->compareStubTemplates($template1, $template2);
        }

        return match ($this->sdkType) {
            'digitalpersona' => $this->compareDigitalPersona($template1, $template2),
            'suprema' => $this->compareSuprema($template1, $template2),
            'futronic' => $this->compareFutronic($template1, $template2),
            default => $this->compareStubTemplates($template1, $template2),
        };
    }

    /**
     * Check if biometric system is enabled.
     */
    public function isBiometricEnabled(): bool
    {
        return (bool) config('hce.biometric.enabled', false);
    }

    /**
     * Get enrolled fingers for a patient.
     */
    public function getEnrolledFingers(Patient $patient): array
    {
        return $patient->fingerprints()
            ->active()
            ->pluck('finger_identifier')
            ->toArray();
    }

    /**
     * Delete a fingerprint enrollment.
     */
    public function deleteFingerprint(PatientFingerprint $fingerprint): bool
    {
        $fingerprint->delete();

        // Check if patient has any remaining fingerprints
        $remaining = PatientFingerprint::where('patient_id', $fingerprint->patient_id)
            ->active()
            ->count();

        if ($remaining === 0) {
            $fingerprint->patient->update([
                'biometric_enrolled' => false,
                'biometric_enrolled_at' => null,
            ]);
        }

        return true;
    }

    // ============================================
    // STUB METHODS FOR TESTING/DEVELOPMENT
    // ============================================

    protected function initializeStubDevice(): array
    {
        $this->deviceInfo = [
            'serial' => 'STUB-DEVICE-001',
            'model' => 'Stub Biometric Device',
            'firmware' => '1.0.0',
        ];

        return [
            'success' => true,
            'device' => $this->deviceInfo,
        ];
    }

    protected function captureStubFingerprint(): array
    {
        // Simulate capture delay
        usleep(500000); // 0.5 seconds

        // Generate a random template (for testing only)
        $template = bin2hex(random_bytes(256));
        $qualityScore = random_int(50, 95);

        return [
            'success' => true,
            'template' => $template,
            'quality_score' => $qualityScore,
            'image_preview' => null, // Base64 image would go here
        ];
    }

    protected function compareStubTemplates(string $template1, string $template2): int
    {
        // Stub comparison - in production use actual SDK comparison
        // For testing, templates with similar prefixes get higher scores
        if ($template1 === $template2) {
            return 100;
        }

        // Random score for testing
        return random_int(0, 80);
    }

    // ============================================
    // SDK INTEGRATION STUBS (Override in production)
    // ============================================

    protected function initializeDigitalPersona(): array
    {
        // TODO: Implement DigitalPersona SDK initialization
        // Example:
        // $result = DPFPInit();
        // if ($result !== DPFP_SUCCESS) {
        //     throw new Exception('DigitalPersona initialization failed');
        // }

        throw new Exception('DigitalPersona SDK not implemented');
    }

    protected function initializeSuprema(): array
    {
        // TODO: Implement Suprema SDK initialization
        // Example:
        // $result = UF_Init();
        // if ($result !== UF_RET_SUCCESS) {
        //     throw new Exception('Suprema initialization failed');
        // }

        throw new Exception('Suprema SDK not implemented');
    }

    protected function initializeFutronic(): array
    {
        // TODO: Implement Futronic SDK initialization

        throw new Exception('Futronic SDK not implemented');
    }

    protected function captureDigitalPersona(): array
    {
        // TODO: Implement DigitalPersona fingerprint capture

        throw new Exception('DigitalPersona SDK not implemented');
    }

    protected function captureSuprema(): array
    {
        // TODO: Implement Suprema fingerprint capture

        throw new Exception('Suprema SDK not implemented');
    }

    protected function captureFutronic(): array
    {
        // TODO: Implement Futronic fingerprint capture

        throw new Exception('Futronic SDK not implemented');
    }

    protected function compareDigitalPersona(string $template1, string $template2): int
    {
        // TODO: Implement DigitalPersona template comparison

        throw new Exception('DigitalPersona SDK not implemented');
    }

    protected function compareSuprema(string $template1, string $template2): int
    {
        // TODO: Implement Suprema template comparison

        throw new Exception('Suprema SDK not implemented');
    }

    protected function compareFutronic(string $template1, string $template2): int
    {
        // TODO: Implement Futronic template comparison

        throw new Exception('Futronic SDK not implemented');
    }
}
