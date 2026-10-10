<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\RateLimiter;

class FhirRateLimiter
{
    /**
     * Handle rate limiting for FHIR API requests.
     * 
     * Default: 100 requests per minute per user/IP
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->getRateLimitKey($request);
        $maxAttempts = config('fhir.rate_limit.max_requests', 100);
        $decayMinutes = config('fhir.rate_limit.decay_minutes', 1);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return $this->rateLimitResponse($key);
        }

        RateLimiter::hit($key, $decayMinutes);

        $response = $next($request);

        // Add rate limit headers to response
        $this->addRateLimitHeaders($response, $key, $maxAttempts);

        return $response;
    }

    /**
     * Generate rate limit key based on user or IP.
     */
    protected function getRateLimitKey(Request $request): string
    {
        // Use user ID if authenticated
        if ($request->has('authenticated_user')) {
            $user = $request->get('authenticated_user');
            return 'fhir:' . ($user->id ?? 'unknown');
        }

        // Fallback to IP
        return 'fhir:' . $request->ip() . ':' . $request->userAgent();
    }

    /**
     * Return 429 Too Many Requests response with OperationOutcome.
     */
    protected function rateLimitResponse(string $key): Response
    {
        $retryAfter = RateLimiter::availableIn($key);

        return response()->json([
            'resourceType' => 'OperationOutcome',
            'issue' => [
                [
                    'severity' => 'error',
                    'code' => 'throttled',
                    'details' => [
                        'text' => 'Rate limit exceeded. Please try again later.',
                    ],
                ],
            ],
        ], 429, [
            'Content-Type' => 'application/fhir+json',
            'Retry-After' => $retryAfter,
        ]);
    }

    /**
     * Add rate limit headers to response.
     */
    protected function addRateLimitHeaders(Response $response, string $key, int $maxAttempts): void
    {
        $remaining = max(0, $maxAttempts - RateLimiter::attempts($key));
        $retryAfter = RateLimiter::availableIn($key);

        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', $remaining);
        $response->headers->set('X-Retry-After', $retryAfter);
    }
}
