<?php

namespace App\Services\Rda;

use App\Models\Encounter;
use App\Models\Patient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class RdaGeneratorService
{
    /**
     * Generate RDA PDF for an encounter.
     */
    public function generateRda(int $patientId, int $encounterId): string
    {
        $patient = Patient::with([
            'allergyIntolerances' => fn ($q) => $q->where('clinical_status', 'active'),
        ])->findOrFail($patientId);

        $encounter = Encounter::with([
            'observations' => fn ($q) => $q->where('category', 'vital-signs'),
            'conditions',
            'medicationRequests',
        ])->findOrFail($encounterId);

        // Verify encounter belongs to patient
        if ($encounter->patient_id !== $patient->id) {
            throw new \InvalidArgumentException('Encounter does not belong to patient');
        }

        $data = [
            'patient' => $patient,
            'encounter' => $encounter,
            'vitalSigns' => $this->formatVitalSigns($encounter->observations),
            'diagnoses' => $encounter->conditions,
            'medications' => $encounter->medicationRequests,
            'allergies' => $patient->allergyIntolerances,
            'generatedAt' => now(),
            'generatedBy' => auth()->user(),
            'institution' => $this->getInstitutionInfo(),
        ];

        $pdf = Pdf::loadView('rda.pdf', $data);
        $pdf->setPaper('letter', 'portrait');

        // Generate filename
        $filename = sprintf(
            'rda/%s_%s_%s.pdf',
            $patient->document_number,
            $encounter->created_at->format('Ymd'),
            now()->format('His')
        );

        // Store PDF
        Storage::disk('local')->put($filename, $pdf->output());

        // Log generation
        activity()
            ->causedBy(auth()->user())
            ->performedOn($encounter)
            ->withProperties([
                'event' => 'rda.generated',
                'patient_id' => $patient->id,
                'encounter_id' => $encounter->id,
                'filename' => $filename,
            ])
            ->log('RDA generado');

        return $filename;
    }

    /**
     * Generate and return PDF download.
     */
    public function downloadRda(int $patientId, int $encounterId): mixed
    {
        $patient = Patient::findOrFail($patientId);
        $encounter = Encounter::with([
            'observations' => fn ($q) => $q->where('category', 'vital-signs'),
            'conditions',
            'medicationRequests',
        ])->findOrFail($encounterId);

        $data = [
            'patient' => $patient,
            'encounter' => $encounter,
            'vitalSigns' => $this->formatVitalSigns($encounter->observations),
            'diagnoses' => $encounter->conditions,
            'medications' => $encounter->medicationRequests,
            'allergies' => $patient->allergyIntolerances()->where('clinical_status', 'active')->get(),
            'generatedAt' => now(),
            'generatedBy' => auth()->user(),
            'institution' => $this->getInstitutionInfo(),
        ];

        $filename = sprintf(
            'RDA_%s_%s.pdf',
            $patient->document_number,
            $encounter->created_at->format('Ymd')
        );

        activity()
            ->causedBy(auth()->user())
            ->performedOn($encounter)
            ->withProperties([
                'event' => 'rda.downloaded',
                'patient_id' => $patient->id,
                'encounter_id' => $encounter->id,
            ])
            ->log('RDA descargado');

        return Pdf::loadView('rda.pdf', $data)
            ->setPaper('letter', 'portrait')
            ->download($filename);
    }

    /**
     * Format vital signs for display.
     */
    protected function formatVitalSigns($observations): array
    {
        $vitalSigns = [];
        $codes = [
            '8480-6' => 'systolic_bp',
            '8462-4' => 'diastolic_bp',
            '8867-4' => 'heart_rate',
            '9279-1' => 'respiratory_rate',
            '8310-5' => 'temperature',
            '59408-5' => 'spo2',
            '29463-7' => 'weight',
            '8302-2' => 'height',
        ];

        foreach ($observations as $obs) {
            $key = $codes[$obs->code] ?? $obs->code;
            $vitalSigns[$key] = [
                'value' => $obs->value,
                'unit' => $obs->unit,
            ];
        }

        // Calculate BMI if weight and height available
        if (isset($vitalSigns['weight'], $vitalSigns['height'])) {
            $weight = $vitalSigns['weight']['value'];
            $height = $vitalSigns['height']['value'] / 100; // cm to m
            $vitalSigns['bmi'] = [
                'value' => round($weight / ($height * $height), 1),
                'unit' => 'kg/m²',
            ];
        }

        return $vitalSigns;
    }

    /**
     * Get institution information.
     */
    protected function getInstitutionInfo(): array
    {
        return [
            'name' => config('app.name', 'Vespot HCE'),
            'nit' => config('hce.institution_nit', ''),
            'address' => config('hce.institution_address', ''),
            'phone' => config('hce.institution_phone', ''),
            'email' => config('hce.institution_email', ''),
        ];
    }
}
