<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Spatie\Activitylog\Facades\CauserResolver;

class FhirAuditMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $response = $next($request);
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        // Log audit entry
        $this->logAuditEntry($request, $response, $duration);

        return $response;
    }

    protected function logAuditEntry(Request $request, Response $response, float $duration): void
    {
        $user = $request->get('authenticated_user');
        $resourceType = $this->extractResourceType($request);
        $resourceId = $this->extractResourceId($request);

        activity()
            ->causedBy($user)
            ->withProperties([
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'status_code' => $response->getStatusCode(),
                'duration_ms' => $duration,
                'ip' => $request->ip(),
            ])
            ->log("FHIR API: {$request->method()} /{$resourceType}" . ($resourceId ? "/{$resourceId}" : ""));
    }

    protected function extractResourceType(Request $request): string
    {
        $segments = $request->segments();
        return $segments[2] ?? 'unknown';
    }

    protected function extractResourceId(Request $request): ?string
    {
        $segments = $request->segments();
        return $segments[3] ?? null;
    }
}
