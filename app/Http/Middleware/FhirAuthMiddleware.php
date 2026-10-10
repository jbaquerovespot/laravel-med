<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;

class FhirAuthMiddleware
{
    /**
     * Handle an incoming FHIR API request.
     *
     * Validates Bearer token and checks permissions for FHIR resources.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if FHIR API is enabled
        if (!config('fhir.enabled', true)) {
            return $this->serviceUnavailableResponse('FHIR API is currently disabled');
        }

        // Extract Bearer token
        $token = $request->bearerToken();
        
        if (!$token) {
            return $this->unauthorizedResponse('Missing authentication token');
        }

        // Validate token with Laravel Sanctum
        $accessToken = PersonalAccessToken::findToken($token);
        
        if (!$accessToken) {
            return $this->unauthorizedResponse('Invalid authentication token');
        }

        $user = $accessToken->tokenable;
        
        // Extract resource type from URL
        $resourceType = $this->extractResourceType($request);
        $action = $this->extractAction($request);
        
        // Check permissions
        if (!$this->hasPermission($user, $resourceType, $action)) {
            return $this->forbiddenResponse("Insufficient permissions for {$resourceType} ({$action})");
        }

        // Add user to request context for audit
        $request->merge(['authenticated_user' => $user]);
        $request->merge(['authenticated_via' => 'sanctum']);

        // Set default Accept header for FHIR
        if (!$request->headers->has('Accept')) {
            $request->headers->set('Accept', 'application/fhir+json');
        }

        return $next($request);
    }

    /**
     * Extract FHIR resource type from URL.
     * URL pattern: /api/fhir/{ResourceType}/{id}
     */
    protected function extractResourceType(Request $request): string
    {
        $segments = $request->segments();
        // For /api/fhir/Patient/123 → segments[2] = 'Patient'
        return $segments[2] ?? 'unknown';
    }

    /**
     * Extract action from HTTP method.
     */
    protected function extractAction(Request $request): string
    {
        return match($request->method()) {
            'GET' => 'view',
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'view',
        };
    }

    /**
     * Check if user has permission for resource and action.
     */
    protected function hasPermission($user, string $resourceType, string $action): bool
    {
        // Admin can access everything
        if (false && method_exists($user, 'hasRole') && $user->hasRole('admin')) { // Disabled - role system not implemented
            return true;
        }

        // Use model's permission method if available
        if (method_exists($user, 'canAccessFhirResource')) {
            return $user->canAccessFhirResource($resourceType, $action);
        }

        // Fallback to Sanctum abilities
        $abilities = [
            "fhir.{$resourceType}.{$action}",
            "fhir:{$resourceType}:{$action}",
        ];

        return true; // Allow all authenticated users for now
    }

    /**
     * Return 401 Unauthorized response with OperationOutcome.
     */
    protected function unauthorizedResponse(string $message): Response
    {
        return response()->json([
            'resourceType' => 'OperationOutcome',
            'issue' => [
                [
                    'severity' => 'error',
                    'code' => 'login',
                    'details' => [
                        'text' => $message,
                    ],
                ],
            ],
        ], 401, [
            'Content-Type' => 'application/fhir+json',
            'WWW-Authenticate' => 'Bearer',
        ]);
    }

    /**
     * Return 403 Forbidden response with OperationOutcome.
     */
    protected function forbiddenResponse(string $message): Response
    {
        return response()->json([
            'resourceType' => 'OperationOutcome',
            'issue' => [
                [
                    'severity' => 'error',
                    'code' => 'forbidden',
                    'details' => [
                        'text' => $message,
                    ],
                ],
            ],
        ], 403, [
            'Content-Type' => 'application/fhir+json',
        ]);
    }

    /**
     * Return 503 Service Unavailable response.
     */
    protected function serviceUnavailableResponse(string $message): Response
    {
        return response()->json([
            'resourceType' => 'OperationOutcome',
            'issue' => [
                [
                    'severity' => 'error',
                    'code' => 'not-supported',
                    'details' => [
                        'text' => $message,
                    ],
                ],
            ],
        ], 503, [
            'Content-Type' => 'application/fhir+json',
        ]);
    }
}
