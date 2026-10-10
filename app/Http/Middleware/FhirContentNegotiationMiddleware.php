<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FhirContentNegotiationMiddleware
{
    /**
     * Handle content negotiation for FHIR API requests.
     * 
     * Supports: application/fhir+json (default), application/fhir+xml
     */
    public function handle(Request $request, Closure $next): Response
    {
        $accept = $request->header('Accept', 'application/fhir+json');
        $contentType = $request->header('Content-Type', 'application/json');

        // Determine response format from Accept header
        $format = $this->determineFormat($accept);
        $request->merge(['_fhir_format' => $format]);

        // Process request
        $response = $next($request);

        // Set response Content-Type header
        $response->headers->set('Content-Type', $this->getContentType($format));

        return $response;
    }

    /**
     * Determine format from Accept header.
     */
    protected function determineFormat(string $accept): string
    {
        // Check for explicit format parameter
        if (str_contains($accept, 'application/fhir+xml') || str_contains($accept, 'application/xml')) {
            return 'xml';
        }

        // Default to JSON (FHIR default)
        return 'json';
    }

    /**
     * Get Content-Type header value for format.
     */
    protected function getContentType(string $format): string
    {
        return match($format) {
            'xml' => 'application/fhir+xml',
            'json' => 'application/fhir+json',
            default => 'application/fhir+json',
        };
    }

    /**
     * Check if XML format is requested.
     */
    public function isXmlRequest(Request $request): bool
    {
        return $request->get('_fhir_format') === 'xml';
    }

    /**
     * Check if JSON format is requested.
     */
    public function isJsonRequest(Request $request): bool
    {
        return $request->get('_fhir_format') === 'json';
    }
}
