<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HCE Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Historias Clínicas Electrónicas system.
    | Complies with Resolución 1995 de 1999, Resolución 1888 de 2025, and Ley 1581 de 2012.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Session Configuration
    |--------------------------------------------------------------------------
    |
    | Session timeout in seconds (15 minutes = 900 seconds).
    | After this period of inactivity, the user will be logged out.
    |
    */
    'session_timeout' => env('HCE_SESSION_TIMEOUT', 900), // 15 minutes

    /*
    |--------------------------------------------------------------------------
    | Authentication Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Maximum number of failed login attempts before account lockout.
    | Lockout duration in minutes.
    |
    */
    'max_login_attempts' => env('HCE_MAX_LOGIN_ATTEMPTS', 5),
    'lockout_duration' => env('HCE_LOCKOUT_DURATION', 30), // minutes

    /*
    |--------------------------------------------------------------------------
    | Password Requirements
    |--------------------------------------------------------------------------
    |
    | Minimum password length and complexity requirements.
    |
    */
    'password_min_length' => 8,
    'password_require_uppercase' => true,
    'password_require_number' => true,
    'password_require_special' => true,

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features.
    |
    */
    'encryption_enabled' => env('HCE_ENCRYPTION_ENABLED', true),
    'biometric_enabled' => env('HCE_BIOMETRIC_ENABLED', false),
    'audit_enabled' => env('HCE_AUDIT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | FHIR Configuration
    |--------------------------------------------------------------------------
    |
    | FHIR R4 specific settings for the HCE system.
    |
    */
    'fhir' => [
        'base_url' => env('FHIR_BASE_URL', config('app.url').'/api/fhir'),
        'r4_enabled' => env('FHIR_R4_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Log Retention
    |--------------------------------------------------------------------------
    |
    | Number of days to retain audit logs.
    | Resolución 1995 requires 20 years retention.
    |
    */
    'audit_retention_days' => env('HCE_AUDIT_RETENTION_DAYS', 7300), // 20 years

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Default pagination settings for lists.
    |
    */
    'pagination_size' => env('HCE_PAGINATION_SIZE', 20),

    /*
    |--------------------------------------------------------------------------
    | Biometric Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for fingerprint biometric authentication.
    | Supported SDKs: digitalpersona, suprema, futronic, stub (for testing)
    |
    */
    'biometric' => [
        'enabled' => env('HCE_BIOMETRIC_ENABLED', false),
        'sdk' => env('HCE_BIOMETRIC_SDK', 'stub'),
        'min_quality_score' => env('HCE_BIOMETRIC_MIN_QUALITY', 60),
        'matching_threshold' => env('HCE_BIOMETRIC_MATCHING_THRESHOLD', 40),
        'max_capture_attempts' => env('HCE_BIOMETRIC_MAX_ATTEMPTS', 3),
        'consent_required' => true,
    ],
];
