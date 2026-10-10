<?php

namespace App\Http\Controllers\Fhir;

use App\Http\Controllers\Controller;
use App\Models\Observation;
use App\Services\Fhir\FhirTransformerService;
use App\Services\Fhir\FhirValidatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FhirObservationController extends Controller
{
    public function __construct(
        protected FhirTransformerService $transformer,
        protected FhirValidatorService $validator
    ) {}

    /**
     * GET /api/fhir/Observation
     * Search for observations with optional filters.
     */
    public function search(Request $request): JsonResponse
    {
        $query = Observation::query()->with(['patient', 'encounter', 'components']);

        // Apply FHIR search parameters
        if ($request->has('patient')) {
            $patientId = $this->extractResourceId($request->patient);
            $query->where('patient_id', $patientId);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('code')) {
            $query->where('code', $request->code);
        }

        if ($request->has('date')) {
            $query->whereDate('effective_datetime', $request->date);
        }

        // Pagination
        $pageSize = min($request->get('_count', config('fhir.default_page_size', 20)), config('fhir.max_page_size', 100));
        
        // Order by most recent first
        $observations = $query->orderByDesc('effective_datetime')->paginate($pageSize);

        // Transform to FHIR Bundle
        $bundle = $this->transformer->transformToBundle($observations->getCollection(), 'Observation');

        // Add FHIR pagination links
        $bundle['link'] = [
            ['relation' => 'self', 'url' => $observations->url(1)],
            ['relation' => 'first', 'url' => $observations->url(1)],
            ...$observations->hasMorePages() ? [['relation' => 'next', 'url' => $observations->nextPageUrl()]] : [],
            ...$observations->previousPageUrl() ? [['relation' => 'previous', 'url' => $observations->previousPageUrl()]] : [],
            ['relation' => 'last', 'url' => $observations->url($observations->lastPage())],
        ];

        return response()->json($bundle, 200, ['Content-Type' => 'application/fhir+json']);
    }

    /**
     * GET /api/fhir/Observation/{id}
     * Get observation by ID.
     */
    public function show(int $id): JsonResponse
    {
        $observation = Observation::with(['patient', 'encounter', 'components'])->findOrFail($id);
        $resource = $this->transformer->transform($observation, 'Observation');

        return response()->json($resource, 200, ['Content-Type' => 'application/fhir+json']);
    }

    /**
     * POST /api/fhir/Observation
     * Create new observation.
     */
    public function store(Request $request): JsonResponse
    {
        $fhirResource = $request->json()->all();

        // Validate FHIR resource
        $validationResult = $this->validator->validate($fhirResource, 'Observation');

        if ($validationResult->isInvalid()) {
            return $this->operationOutcomeResponse($validationResult->getErrorMessages(), 400);
        }

        // Transform from FHIR to Laravel model
        $observation = $this->transformer->transformFromFhir($fhirResource, 'Observation');
        $observation->save();

        // Transform response to FHIR
        $resource = $this->transformer->transform($observation, 'Observation');

        return response()->json($resource, 201, [
            'Content-Type' => 'application/fhir+json',
            'Location' => url("/api/fhir/Observation/{$observation->id}"),
        ]);
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
