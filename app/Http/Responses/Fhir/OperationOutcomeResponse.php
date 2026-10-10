<?php

namespace App\Http\Responses\Fhir;

use Illuminate\Http\JsonResponse;

class OperationOutcomeResponse
{
    public static function error(string $message, string $severity = 'error', string $code = 'processing'): JsonResponse
    {
        return response()->json([
            'resourceType' => 'OperationOutcome',
            'issue' => [[
                'severity' => $severity,
                'code' => $code,
                'details' => ['text' => $message],
            ]],
        ], 400, ['Content-Type' => 'application/fhir+json']);
    }

    public static function unauthorized(string $message): JsonResponse
    {
        return response()->json([
            'resourceType' => 'OperationOutcome',
            'issue' => [[
                'severity' => 'error',
                'code' => 'login',
                'details' => ['text' => $message],
            ]],
        ], 401, ['Content-Type' => 'application/fhir+json', 'WWW-Authenticate' => 'Bearer']);
    }

    public static function forbidden(string $message): JsonResponse
    {
        return response()->json([
            'resourceType' => 'OperationOutcome',
            'issue' => [[
                'severity' => 'error',
                'code' => 'forbidden',
                'details' => ['text' => $message],
            ]],
        ], 403, ['Content-Type' => 'application/fhir+json']);
    }

    public static function notFound(string $message): JsonResponse
    {
        return response()->json([
            'resourceType' => 'OperationOutcome',
            'issue' => [[
                'severity' => 'error',
                'code' => 'not-found',
                'details' => ['text' => $message],
            ]],
        ], 404, ['Content-Type' => 'application/fhir+json']);
    }

    public static function information(string $message): JsonResponse
    {
        return response()->json([
            'resourceType' => 'OperationOutcome',
            'issue' => [[
                'severity' => 'information',
                'code' => 'informational',
                'details' => ['text' => $message],
            ]],
        ], 200, ['Content-Type' => 'application/fhir+json']);
    }
}
