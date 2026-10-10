<?php

namespace App\Http\Controllers\Fhir;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\Fhir\FhirTransformerService;
use App\Services\Fhir\FhirValidatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FhirPatientController extends Controller
{
    public function __construct(
        protected FhirTransformerService $transformer,
        protected FhirValidatorService $validator
    ) {}

    /**
     * GET /api/fhir/Patient
     * Search for patients with optional filters.
     */
    public function search(Request $request): JsonResponse
    {
        $query = Patient::query();

        // Apply FHIR search parameters
        if ($request->has('identifier')) {
            $query->where('document_number', $request->identifier);
        }

        if ($request->has('name')) {
            $name = $request->name;
            $query->where(function ($q) use ($name) {
                $q->where('last_name', 'like', "%{$name}%")
                  ->orWhere('first_name', 'like', "%{$name}%");
            });
        }

        if ($request->has('birthdate')) {
            $query->where('birth_date', $request->birthdate);
        }

        if ($request->has('gender')) {
            $query->where('gender', $request->gender);
        }

        if ($request->has('active')) {
            $query->where('active', filter_var($request->active, FILTER_VALIDATE_BOOLEAN));
        }

        // Pagination
        $pageSize = min($request->get('_count', config('fhir.default_page_size', 20)), config('fhir.max_page_size', 100));
        $patients = $query->paginate($pageSize);

        // Transform to FHIR Bundle
        $bundle = $this->transformer->transformToBundle($patients->getCollection(), 'Patient');

        // Add FHIR pagination links
        $bundle['link'] = [
            ['relation' => 'self', 'url' => $patients->url(1)],
            ['relation' => 'first', 'url' => $patients->url(1)],
            ...$patients->hasMorePages() ? [['relation' => 'next', 'url' => $patients->nextPageUrl()]] : [],
            ...$patients->previousPageUrl() ? [['relation' => 'previous', 'url' => $patients->previousPageUrl()]] : [],
            ['relation' => 'last', 'url' => $patients->url($patients->lastPage())],
        ];

        return response()->json($bundle, 200, ['Content-Type' => 'application/fhir+json']);
    }

    /**
     * GET /api/fhir/Patient/{id}
     * Get patient by ID.
     */
    public function show(int $id): JsonResponse
    {
        $patient = Patient::findOrFail($id);
        $resource = $this->transformer->transform($patient, 'Patient');

        return response()->json($resource, 200, ['Content-Type' => 'application/fhir+json']);
    }

    /**
     * POST /api/fhir/Patient
     * Create new patient.
     */
    public function store(Request $request): JsonResponse
    {
        $fhirResource = $request->json()->all();

        // Validate FHIR resource
        $validationResult = $this->validator->validate($fhirResource, 'Patient');

        if ($validationResult->isInvalid()) {
            return $this->operationOutcomeResponse($validationResult->getErrorMessages(), 400);
        }

        // Transform from FHIR to Laravel model
        $patient = $this->transformer->transformFromFhir($fhirResource, 'Patient');
        $patient->save();

        // Transform response to FHIR
        $resource = $this->transformer->transform($patient, 'Patient');

        return response()->json($resource, 201, [
            'Content-Type' => 'application/fhir+json',
            'Location' => url("/api/fhir/Patient/{$patient->id}"),
        ]);
    }

    /**
     * PUT /api/fhir/Patient/{id}
     * Update patient.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $patient = Patient::findOrFail($id);
        $fhirResource = $request->json()->all();

        // Validate FHIR resource
        $validationResult = $this->validator->validate($fhirResource, 'Patient');

        if ($validationResult->isInvalid()) {
            return $this->operationOutcomeResponse($validationResult->getErrorMessages(), 400);
        }

        // Update patient
        $data = $this->transformer->transformFromFhir($fhirResource, 'Patient');
        $patient->update($data->toArray());
        $patient->refresh();

        $resource = $this->transformer->transform($patient, 'Patient');

        return response()->json($resource, 200, ['Content-Type' => 'application/fhir+json']);
    }

    /**
     * DELETE /api/fhir/Patient/{id}
     * Delete patient (soft delete).
     */
    public function destroy(int $id): JsonResponse
    {
        $patient = Patient::findOrFail($id);
        $patient->delete(); // Soft delete

        return response()->json([
            'resourceType' => 'OperationOutcome',
            'issue' => [
                [
                    'severity' => 'information',
                    'code' => 'informational',
                    'details' => [
                        'text' => "Patient {$id} successfully deleted",
                    ],
                ],
            ],
        ], 200, ['Content-Type' => 'application/fhir+json']);
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
}
