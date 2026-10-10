<?php

namespace App\Http\Controllers\Fhir;

use App\Http\Controllers\Controller;
use App\Models\Condition;
use App\Services\Fhir\FhirTransformerService;
use App\Services\Fhir\FhirValidatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FhirConditionController extends Controller
{
    public function __construct(
        protected FhirTransformerService $transformer,
        protected FhirValidatorService $validator
    ) {}

    /**
     * GET /api/fhir/Condition
     * Search for conditions with optional filters.
     */
    public function search(Request $request): JsonResponse
    {
        $query = Condition::query()->with(['patient', 'encounter', 'recorder']);

        // Apply FHIR search parameters
        if ($request->has('patient')) {
            $patientId = $this->extractResourceId($request->patient);
            $query->where('patient_id', $patientId);
        }

        if ($request->has('clinical-status')) {
            $query->where('clinical_status', $request->get('clinical-status'));
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Pagination
        $pageSize = min($request->get('_count', config('fhir.default_page_size', 20)), config('fhir.max_page_size', 100));
        $conditions = $query->paginate($pageSize);

        // Transform to FHIR Bundle
        $bundle = $this->transformer->transformToBundle($conditions->getCollection(), 'Condition');

        // Add FHIR pagination links
        $bundle['link'] = [
            ['relation' => 'self', 'url' => $conditions->url(1)],
            ['relation' => 'first', 'url' => $conditions->url(1)],
            ...$conditions->hasMorePages() ? [['relation' => 'next', 'url' => $conditions->nextPageUrl()]] : [],
            ...$conditions->previousPageUrl() ? [['relation' => 'previous', 'url' => $conditions->previousPageUrl()]] : [],
            ['relation' => 'last', 'url' => $conditions->url($conditions->lastPage())],
        ];

        return response()->json($bundle, 200, ['Content-Type' => 'application/fhir+json']);
    }

    /**
     * GET /api/fhir/Condition/{id}
     * Get condition by ID.
     */
    public function show(int $id): JsonResponse
    {
        $condition = Condition::with(['patient', 'encounter', 'recorder'])->findOrFail($id);
        $resource = $this->transformer->transform($condition, 'Condition');

        return response()->json($resource, 200, ['Content-Type' => 'application/fhir+json']);
    }

    /**
     * POST /api/fhir/Condition
     * Create new condition.
     */
    public function store(Request $request): JsonResponse
    {
        $fhirResource = $request->json()->all();

        // Validate FHIR resource
        $validationResult = $this->validator->validate($fhirResource, 'Condition');

        if ($validationResult->isInvalid()) {
            return $this->operationOutcomeResponse($validationResult->getErrorMessages(), 400);
        }

        // Transform from FHIR to Laravel model
        $condition = $this->transformer->transformFromFhir($fhirResource, 'Condition');
        $condition->save();

        // Transform response to FHIR
        $resource = $this->transformer->transform($condition, 'Condition');

        return response()->json($resource, 201, [
            'Content-Type' => 'application/fhir+json',
            'Location' => url("/api/fhir/Condition/{$condition->id}"),
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
