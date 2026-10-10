<?php

namespace App\Services\Fhir;

use App\Models\Patient;
use App\Models\Encounter;
use App\Services\Fhir\Transformers\PatientToFhirTransformer;
use App\Services\Fhir\Transformers\EncounterToFhirTransformer;
use App\Services\Fhir\Transformers\ConditionToFhirTransformer;
use App\Services\Fhir\Transformers\ObservationToFhirTransformer;
use App\Services\Fhir\Transformers\MedicationRequestToFhirTransformer;
use App\Services\Fhir\Transformers\ServiceRequestToFhirTransformer;
use App\Services\Fhir\Transformers\AllergyIntoleranceToFhirTransformer;

/**
 * Service for generating RDA (Resumen Digital de Atención).
 * 
 * Generates FHIR Bundle with Composition and all related clinical resources
 * according to Resolución 1888 de 2025 (Colombia).
 */
class RdaGeneratorService
{
    public function __construct(
        protected FhirTransformerService $transformer
    ) {}

    /**
     * Generate RDA Bundle for a patient encounter.
     *
     * @param int $patientId Patient ID
     * @param int $encounterId Encounter ID
     * @return array FHIR Bundle type "document"
     */
    public function generateRda(int $patientId, int $encounterId): array
    {
        // Load patient and encounter
        $patient = Patient::with(['generalPractitioner'])->findOrFail($patientId);
        $encounter = Encounter::with(['patient', 'practitioner', 'location'])
            ->findOrFail($encounterId);

        // Initialize bundle entries
        $entries = [];

        // 1. Add Composition (RDA document structure)
        $composition = $this->buildComposition($patient, $encounter);
        $entries[] = [
            'fullUrl' => url("/api/fhir/Composition/rda-{$encounterId}"),
            'resource' => $composition,
        ];

        // 2. Add Patient
        $patientTransformer = new PatientToFhirTransformer();
        $patientFhir = $patientTransformer->transform($patient);
        $entries[] = [
            'fullUrl' => url("/api/fhir/Patient/{$patient->id}"),
            'resource' => $patientFhir,
        ];

        // 3. Add Encounter
        $encounterTransformer = new EncounterToFhirTransformer();
        $encounterFhir = $encounterTransformer->transform($encounter);
        $entries[] = [
            'fullUrl' => url("/api/fhir/Encounter/{$encounter->id}"),
            'resource' => $encounterFhir,
        ];

        // 4. Add Conditions (diagnósticos)
        $conditions = $encounter->conditions()->with(['patient', 'encounter'])->get();
        $conditionTransformer = new ConditionToFhirTransformer();
        foreach ($conditions as $condition) {
            $entries[] = [
                'fullUrl' => url("/api/fhir/Condition/{$condition->id}"),
                'resource' => $conditionTransformer->transform($condition),
            ];
        }

        // 5. Add Observations (signos vitales, exámenes)
        $observations = $encounter->observations()->with(['patient', 'encounter', 'components'])->get();
        $observationTransformer = new ObservationToFhirTransformer();
        foreach ($observations as $observation) {
            $entries[] = [
                'fullUrl' => url("/api/fhir/Observation/{$observation->id}"),
                'resource' => $observationTransformer->transform($observation),
            ];
        }

        // 6. Add MedicationRequests (tratamientos)
        $medicationRequests = $encounter->medicationRequests()->with(['patient', 'encounter'])->get();
        $medicationRequestTransformer = new MedicationRequestToFhirTransformer();
        foreach ($medicationRequests as $medicationRequest) {
            $entries[] = [
                'fullUrl' => url("/api/fhir/MedicationRequest/{$medicationRequest->id}"),
                'resource' => $medicationRequestTransformer->transform($medicationRequest),
            ];
        }

        // 7. Add ServiceRequests (órdenes de servicio)
        $serviceRequests = $encounter->serviceRequests()->with(['patient', 'encounter'])->get();
        $serviceRequestTransformer = new ServiceRequestToFhirTransformer();
        foreach ($serviceRequests as $serviceRequest) {
            $entries[] = [
                'fullUrl' => url("/api/fhir/ServiceRequest/{$serviceRequest->id}"),
                'resource' => $serviceRequestTransformer->transform($serviceRequest),
            ];
        }

        // 8. Add AllergyIntolerance (antecedentes alérgicos)
        $allergies = $patient->allergyIntolerances()->with(['patient'])->get();
        $allergyTransformer = new AllergyIntoleranceToFhirTransformer();
        foreach ($allergies as $allergy) {
            $entries[] = [
                'fullUrl' => url("/api/fhir/AllergyIntolerance/{$allergy->id}"),
                'resource' => $allergyTransformer->transform($allergy),
            ];
        }

        // Build Bundle
        return [
            'resourceType' => 'Bundle',
            'type' => 'document',
            'identifier' => [
                'system' => url('/rda'),
                'value' => "RDA-{$encounterId}-" . now()->format('YmdHis'),
            ],
            'timestamp' => now()->toIso8601String(),
            'entry' => $entries,
        ];
    }

    /**
     * Build RDA Composition resource.
     */
    protected function buildComposition(Patient $patient, Encounter $encounter): array
    {
        $sections = [];

        // Sección 1: Motivo de consulta
        if ($encounter->reason_text) {
            $sections[] = [
                'title' => 'Motivo de consulta',
                'code' => [
                    'coding' => [[
                        'system' => 'http://snomed.info/sct',
                        'code' => $encounter->reason_code ?? 'unknown',
                        'display' => $encounter->reason_text,
                    ]],
                ],
                'text' => [
                    'status' => 'generated',
                    'div' => "<div>{$encounter->reason_text}</div>",
                ],
            ];
        }

        // Sección 2: Antecedentes (alergias)
        $allergies = $patient->allergyIntolerances;
        if ($allergies->isNotEmpty()) {
            $sections[] = [
                'title' => 'Antecedentes alérgicos',
                'code' => [
                    'coding' => [[
                        'system' => 'http://loinc.org',
                        'code' => '48765-2',
                        'display' => 'Allergies',
                    ]],
                ],
                'entry' => $allergies->map(fn($a) => [
                    'reference' => "AllergyIntolerance/{$a->id}",
                ])->toArray(),
            ];
        }

        // Sección 3: Diagnósticos
        $conditions = $encounter->conditions;
        if ($conditions->isNotEmpty()) {
            $sections[] = [
                'title' => 'Diagnósticos',
                'code' => [
                    'coding' => [[
                        'system' => 'http://loinc.org',
                        'code' => '11450-4',
                        'display' => 'Problem list',
                    ]],
                ],
                'entry' => $conditions->map(fn($c) => [
                    'reference' => "Condition/{$c->id}",
                ])->toArray(),
            ];
        }

        // Sección 4: Signos vitales
        $vitals = $encounter->observations()->where('category', 'vital-signs')->get();
        if ($vitals->isNotEmpty()) {
            $sections[] = [
                'title' => 'Signos vitales',
                'code' => [
                    'coding' => [[
                        'system' => 'http://loinc.org',
                        'code' => '8716-3',
                        'display' => 'Vital signs',
                    ]],
                ],
                'entry' => $vitals->map(fn($v) => [
                    'reference' => "Observation/{$v->id}",
                ])->toArray(),
            ];
        }

        // Sección 5: Tratamientos
        $medications = $encounter->medicationRequests;
        if ($medications->isNotEmpty()) {
            $sections[] = [
                'title' => 'Tratamientos',
                'code' => [
                    'coding' => [[
                        'system' => 'http://loinc.org',
                        'code' => '10160-0',
                        'display' => 'Medications',
                    ]],
                ],
                'entry' => $medications->map(fn($m) => [
                    'reference' => "MedicationRequest/{$m->id}",
                ])->toArray(),
            ];
        }

        // Sección 6: Órdenes
        $orders = $encounter->serviceRequests;
        if ($orders->isNotEmpty()) {
            $sections[] = [
                'title' => 'Órdenes',
                'code' => [
                    'coding' => [[
                        'system' => 'http://loinc.org',
                        'code' => '18776-5',
                        'display' => 'Orders',
                    ]],
                ],
                'entry' => $orders->map(fn($o) => [
                    'reference' => "ServiceRequest/{$o->id}",
                ])->toArray(),
            ];
        }

        return [
            'resourceType' => 'Composition',
            'identifier' => [
                'system' => url('/rda'),
                'value' => "RDA-{$encounter->id}",
            ],
            'status' => 'final',
            'type' => config('fhir.rda.composition_type', [
                'system' => 'http://hl7.org/fhir/composition-type',
                'code' => '11488-4',
                'display' => 'Consult note',
            ]),
            'category' => [[
                'coding' => [[
                    'system' => 'http://loinc.org',
                    'code' => 'LP173421-1',
                    'display' => 'Report',
                ]],
            ]],
            'subject' => [
                'reference' => "Patient/{$patient->id}",
                'display' => $patient->full_name,
            ],
            'encounter' => [
                'reference' => "Encounter/{$encounter->id}",
            ],
            'date' => now()->toIso8601String(),
            'author' => [[
                'reference' => "Practitioner/{$encounter->practitioner_id}",
                'display' => $encounter->practitioner?->name ?? 'Unknown',
            ]],
            'title' => "Resumen Digital de Atención - {$encounter->start_datetime->format('Y-m-d H:i')}",
            'section' => $sections,
        ];
    }
}
