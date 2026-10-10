<?php

namespace App\Http\Controllers\Fhir;

use App\Http\Controllers\Controller;
use App\Models\Encounter;
use App\Services\Fhir\FhirTransformerService;
use App\Services\Fhir\FhirValidatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FhirEncounterController extends Controller
{
    public function __construct(
        protected FhirTransformerService $transformer,
        protected FhirValidatorService $validator
    ) {}

    /**
     * GET /api/fhir/Encounter
     * Search for encounters with optional filters.
     */
    public function search(Request $request): JsonResponse
    {
        $query = Encounter::query()->with(['patient', 'practitioner', 'location']);

        // Apply FHIR search parameters
        if ($request->has('patient')) {
            $patientId = $this->extractResourceId($request->patient);
            $query->where('patient_id', $patientId);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('class')) {
            $query->where('class_code', $request->class);
        }

        if ($request->has('date')) {
            $query->whereDate('start_datetime', $request->date);
        }

        // Pagination
        $pageSize = min($request->get('_count', config('fhir.default_page_size', 20)), config('fhir.max_page_size', 100));
        $encounters = $query->paginate($pageSize);

        // Transform to FHIR Bundle
        $bundle = $this->transformer->transformToBundle($encounters->getCollection(), 'Encounter');

        // Add FHIR pagination links
        $bundle['link'] = [
            ['relation' => 'self', 'url' => $encounters->url(1)],
            ['relation' => 'first', 'url' => $encounters->url(1)],
            ...$encounters->hasMorePages() ? [['relation' => 'next', 'url' => $encounters->nextPageUrl()]] : [],
            ...$encounters->previousPageUrl() ? [['relation' => 'previous', 'url' => $encounters->previousPageUrl()]] : [],
            ['relation' => 'last', 'url' => $encounters->url($encounters->lastPage())],
        ];

        return response()->json($bundle, 200, ['Content-Type' => 'application/fhir+json']);
    }

    /**
     * GET /api/fhir/Encounter/{id}
     * Get encounter by ID.
     */
    public function show(int $id): JsonResponse
    {
        $encounter = Encounter::with(['patient', 'practitioner', 'location'])->findOrFail($id);
        $resource = $this->transformer->transform($encounter, 'Encounter');

        return response()->json($resource, 200, ['Content-Type' => 'application/fhir+json']);
    }

    /**
     * POST /api/fhir/Encounter
     * Create new encounter.
     */
    public function store(Request $request): JsonResponse
    {
        $fhirResource = $request->json()->all();

        // Validate FHIR resource
        $validationResult = $this->validator->validate($fhirResource, 'Encounter');

        if ($validationResult->isInvalid()) {
            return $this->operationOutcomeResponse($validationResult->getErrorMessages(), 400);
        }

        // Transform from FHIR to Laravel model
        $encounter = $this->transformer->transformFromFhir($fhirResource, 'Encounter');
        $encounter->save();

        // Transform response to FHIR
        $resource = $this->transformer->transform($encounter, 'Encounter');

        return response()->json($resource, 201, [
            'Content-Type' => 'application/fhir+json',
            'Location' => url("/api/fhir/Encounter/{$encounter->id}"),
        ]);
    }

    /**
     * PUT /api/fhir/Encounter/{id}
     * Update encounter.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $encounter = Encounter::findOrFail($id);
        $fhirResource = $request->json()->all();

        // Validate FHIR resource
        $validationResult = $this->validator->validate($fhirResource, 'Encounter');

        if ($validationResult->isInvalid()) {
            return $this->operationOutcomeResponse($validationResult->getErrorMessages(), 400);
        }

        // Update encounter
        $data = $this->transformer->transformFromFhir($fhirResource, 'Encounter');
        $encounter->update($data->toArray());
        $encounter->refresh();

        $resource = $this->transformer->transform($encounter, 'Encounter');

        return response()->json($resource, 200, ['Content-Type' => 'application/fhir+json']);
    }

    /**
     * Generate OperationOutcome response for errors.
     */
    protected function operationOutcomeResponse(array $errors, int $statusCode): JsonResponse
    {
        return response()->json([
            'resourceType' => 'OperationOutcome',
            'issue' => collect($errors)->map(fn($error) => [
                'severity' => 'error',
                'code' => 'processing',
                'details' => [
                    'text' => $error,
                ],
            ])->toArray(),
        ], $statusCode, ['Content-Type' => 'application/fhir+json']);
    }

    /**
     * Extract resource ID from FHIR reference.
     */
    protected function extractResourceId(string $reference): ?int
    {
        $parts = explode('/', $reference);
        $id = end($parts);
        return is_numeric($id) ? (int) $id : null;
    }
}
