<?php

namespace App\Http\Controllers\Fhir;

use App\Http\Controllers\Controller;
use App\Models\AllergyIntolerance;
use App\Services\Fhir\FhirTransformerService;
use App\Services\Fhir\FhirValidatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FhirAllergyIntoleranceController extends Controller
{
    public function __construct(
        protected FhirTransformerService $transformer,
        protected FhirValidatorService $validator
    ) {}

    public function search(Request $request): JsonResponse
    {
        $query = AllergyIntolerance::query()->with(['patient']);

        if ($request->has('patient')) {
            $patientId = $this->extractResourceId($request->patient);
            $query->where('patient_id', $patientId);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $pageSize = min($request->get('_count', config('fhir.default_page_size', 20)), config('fhir.max_page_size', 100));
        $resources = $query->paginate($pageSize);

        $bundle = $this->transformer->transformToBundle($resources->getCollection(), 'AllergyIntolerance');

        $bundle['link'] = [
            ['relation' => 'self', 'url' => $resources->url(1)],
            ['relation' => 'first', 'url' => $resources->url(1)],
            ...$resources->hasMorePages() ? [['relation' => 'next', 'url' => $resources->nextPageUrl()]] : [],
            ...$resources->previousPageUrl() ? [['relation' => 'previous', 'url' => $resources->previousPageUrl()]] : [],
            ['relation' => 'last', 'url' => $resources->url($resources->lastPage())],
        ];

        return response()->json($bundle, 200, ['Content-Type' => 'application/fhir+json']);
    }

    public function show(int $id): JsonResponse
    {
        $resource = AllergyIntolerance::with(['patient'])->findOrFail($id);
        $fhirResource = $this->transformer->transform($resource, 'AllergyIntolerance');

        return response()->json($fhirResource, 200, ['Content-Type' => 'application/fhir+json']);
    }

    public function store(Request $request): JsonResponse
    {
        $fhirResource = $request->json()->all();

        $validationResult = $this->validator->validate($fhirResource, 'AllergyIntolerance');

        if ($validationResult->isInvalid()) {
            return $this->operationOutcomeResponse($validationResult->getErrorMessages(), 400);
        }

        $model = $this->transformer->transformFromFhir($fhirResource, 'AllergyIntolerance');
        $model->save();

        $resource = $this->transformer->transform($model, 'AllergyIntolerance');

        return response()->json($resource, 201, [
            'Content-Type' => 'application/fhir+json',
            'Location' => url("/api/fhir/AllergyIntolerance/{$model->id}"),
        ]);
    }

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

    protected function extractResourceId(string $reference): ?int
    {
        $parts = explode('/', $reference);
        $id = end($parts);
        return is_numeric($id) ? (int) $id : null;
    }
}
