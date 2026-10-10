<?php

namespace App\Http\Controllers\Fhir;

use App\Http\Controllers\Controller;
use App\Services\Fhir\FhirTransformerService;
use App\Services\Fhir\FhirValidatorService;
use App\Services\Fhir\RdaGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FhirBundleController extends Controller
{
    public function __construct(
        protected FhirTransformerService $transformer,
        protected FhirValidatorService $validator,
        protected RdaGeneratorService $rdaGenerator
    ) {}

    /**
     * GET /api/fhir/$rda
     * Generate RDA (Resumen Digital de Atención) Bundle.
     */
    public function generateRda(Request $request): JsonResponse
    {
        $patientId = $request->get('patient_id');
        $encounterId = $request->get('encounter_id');

        // Validate required parameters
        if (!$patientId || !$encounterId) {
            return $this->operationOutcomeResponse([
                'Missing required parameters: patient_id and encounter_id',
            ], 400);
        }

        try {
            // Generate RDA Bundle
            $bundle = $this->rdaGenerator->generateRda((int) $patientId, (int) $encounterId);

            return response()->json($bundle, 200, [
                'Content-Type' => 'application/fhir+json',
            ]);
        } catch (\Exception $e) {
            return $this->operationOutcomeResponse([
                "Error generating RDA: {$e->getMessage()}",
            ], 500);
        }
    }

    /**
     * POST /api/fhir/Bundle
     * Process FHIR Bundle transaction.
     */
    public function transaction(Request $request): JsonResponse
    {
        $bundle = $request->json()->all();

        // Validate bundle structure
        if (!isset($bundle['resourceType']) || $bundle['resourceType'] !== 'Bundle') {
            return $this->operationOutcomeResponse([
                'Invalid bundle: resourceType must be Bundle',
            ], 400);
        }

        if (!isset($bundle['type']) || $bundle['type'] !== 'transaction') {
            return $this->operationOutcomeResponse([
                'Only transaction bundles are supported',
            ], 400);
        }

        // Process entries
        $responseEntries = [];
        foreach ($bundle['entry'] ?? [] as $index => $entry) {
            $resource = $entry['resource'] ?? null;
            
            if (!$resource) {
                $responseEntries[] = [
                    'resource' => $this->buildOperationOutcome('error', 'Entry missing resource'),
                ];
                continue;
            }

            // Validate resource
            $resourceType = $resource['resourceType'] ?? null;
            if (!$resourceType) {
                $responseEntries[] = [
                    'resource' => $this->buildOperationOutcome('error', 'Resource missing resourceType'),
                ];
                continue;
            }

            // Validate against FHIR schema
            $validationResult = $this->validator->validate($resource, $resourceType);
            
            if ($validationResult->isInvalid()) {
                $responseEntries[] = [
                    'resource' => $this->buildOperationOutcome('error', implode(', ', $validationResult->getErrorMessages())),
                ];
                continue;
            }

            // Transform and save
            try {
                $model = $this->transformer->transformFromFhir($resource, $resourceType);
                $model->save();

                // Transform back to FHIR for response
                $savedResource = $this->transformer->transform($model, $resourceType);

                $responseEntries[] = [
                    'fullUrl' => url("/api/fhir/{$resourceType}/{$model->id}"),
                    'resource' => $savedResource,
                    'status' => '201',
                ];
            } catch (\Exception $e) {
                $responseEntries[] = [
                    'resource' => $this->buildOperationOutcome('error', $e->getMessage()),
                ];
            }
        }

        // Build response bundle
        $responseBundle = [
            'resourceType' => 'Bundle',
            'type' => 'transaction-response',
            'entry' => $responseEntries,
        ];

        return response()->json($responseBundle, 200, [
            'Content-Type' => 'application/fhir+json',
        ]);
    }

    /**
     * GET /api/fhir/metadata
     * Return CapabilityStatement for this FHIR server.
     */
    public function capabilityStatement(): JsonResponse
    {
        $statement = [
            'resourceType' => 'CapabilityStatement',
            'status' => 'active',
            'date' => now()->toIso8601String(),
            'kind' => 'instance',
            'fhirVersion' => '4.0.1',
            'format' => ['application/fhir+json', 'application/fhir+xml'],
            'rest' => [[
                'mode' => 'server',
                'resource' => [
                    [
                        'type' => 'Patient',
                        'interaction' => [
                            ['code' => 'read'],
                            ['code' => 'search-type'],
                            ['code' => 'create'],
                            ['code' => 'update'],
                            ['code' => 'delete'],
                        ],
                        'searchParam' => [
                            ['name' => 'identifier', 'type' => 'token'],
                            ['name' => 'name', 'type' => 'string'],
                            ['name' => 'birthdate', 'type' => 'date'],
                            ['name' => 'gender', 'type' => 'token'],
                            ['name' => 'active', 'type' => 'token'],
                        ],
                    ],
                    [
                        'type' => 'Encounter',
                        'interaction' => [
                            ['code' => 'read'],
                            ['code' => 'search-type'],
                            ['code' => 'create'],
                            ['code' => 'update'],
                        ],
                        'searchParam' => [
                            ['name' => 'patient', 'type' => 'reference'],
                            ['name' => 'status', 'type' => 'token'],
                            ['name' => 'date', 'type' => 'date'],
                        ],
                    ],
                    [
                        'type' => 'Observation',
                        'interaction' => [
                            ['code' => 'read'],
                            ['code' => 'search-type'],
                            ['code' => 'create'],
                        ],
                        'searchParam' => [
                            ['name' => 'patient', 'type' => 'reference'],
                            ['name' => 'category', 'type' => 'token'],
                            ['name' => 'code', 'type' => 'token'],
                            ['name' => 'date', 'type' => 'date'],
                        ],
                    ],
                    [
                        'type' => 'Condition',
                        'interaction' => [
                            ['code' => 'read'],
                            ['code' => 'search-type'],
                            ['code' => 'create'],
                        ],
                        'searchParam' => [
                            ['name' => 'patient', 'type' => 'reference'],
                            ['name' => 'clinical-status', 'type' => 'token'],
                        ],
                    ],
                    [
                        'type' => 'MedicationRequest',
                        'interaction' => [
                            ['code' => 'read'],
                            ['code' => 'search-type'],
                            ['code' => 'create'],
                        ],
                        'searchParam' => [
                            ['name' => 'patient', 'type' => 'reference'],
                            ['name' => 'status', 'type' => 'token'],
                        ],
                    ],
                    [
                        'type' => 'ServiceRequest',
                        'interaction' => [
                            ['code' => 'read'],
                            ['code' => 'search-type'],
                            ['code' => 'create'],
                        ],
                        'searchParam' => [
                            ['name' => 'patient', 'type' => 'reference'],
                            ['name' => 'status', 'type' => 'token'],
                        ],
                    ],
                    [
                        'type' => 'AllergyIntolerance',
                        'interaction' => [
                            ['code' => 'read'],
                            ['code' => 'search-type'],
                            ['code' => 'create'],
                        ],
                        'searchParam' => [
                            ['name' => 'patient', 'type' => 'reference'],
                            ['name' => 'clinical-status', 'type' => 'token'],
                        ],
                    ],
                    [
                        'type' => 'Practitioner',
                        'interaction' => [
                            ['code' => 'read'],
                            ['code' => 'search-type'],
                            ['code' => 'create'],
                        ],
                        'searchParam' => [
                            ['name' => 'name', 'type' => 'string'],
                            ['name' => 'identifier', 'type' => 'token'],
                        ],
                    ],
                ],
            ]],
        ];

        return response()->json($statement, 200, [
            'Content-Type' => 'application/fhir+json',
        ]);
    }

    /**
     * Generate OperationOutcome response.
     */
    protected function operationOutcomeResponse(array $errors, int $statusCode): JsonResponse
    {
        return response()->json([
            'resourceType' => 'OperationOutcome',
            'issue' => collect($errors)->map(fn($error) => [
                'severity' => 'error',
                'code' => 'processing',
                'details' => ['text' => $error],
            ])->toArray(),
        ], $statusCode, ['Content-Type' => 'application/fhir+json']);
    }

    /**
     * Build OperationOutcome resource.
     */
    protected function buildOperationOutcome(string $severity, string $details): array
    {
        return [
            'resourceType' => 'OperationOutcome',
            'issue' => [[
                'severity' => $severity,
                'code' => 'processing',
                'details' => ['text' => $details],
            ]],
        ];
    }
}
