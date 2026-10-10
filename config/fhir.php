<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FHIR API Enabled
    |--------------------------------------------------------------------------
    |
    | Control whether the FHIR API is enabled. Set to false to disable all
    | FHIR endpoints without removing routes or code.
    |
    */
    'enabled' => env('FHIR_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | FHIR Resources Configuration
    |--------------------------------------------------------------------------
    |
    | Define which FHIR resources are supported, their corresponding Eloquent
    | models, transformers, and searchable parameters.
    |
    */
    'resources' => [
        'Patient' => [
            'model' => App\Models\Patient::class,
            'transformer' => App\Services\Fhir\Transformers\PatientToFhirTransformer::class,
            'search_params' => ['identifier', 'name', 'birthdate', 'gender', 'active'],
        ],
        'Encounter' => [
            'model' => App\Models\Encounter::class,
            'transformer' => App\Services\Fhir\Transformers\EncounterToFhirTransformer::class,
            'search_params' => ['patient', 'status', 'class', 'date'],
        ],
        'Observation' => [
            'model' => App\Models\Observation::class,
            'transformer' => App\Services\Fhir\Transformers\ObservationToFhirTransformer::class,
            'search_params' => ['patient', 'category', 'code', 'date'],
        ],
        'Condition' => [
            'model' => App\Models\Condition::class,
            'transformer' => App\Services\Fhir\Transformers\ConditionToFhirTransformer::class,
            'search_params' => ['patient', 'clinical-status', 'category'],
        ],
        'MedicationRequest' => [
            'model' => App\Models\MedicationRequest::class,
            'transformer' => App\Services\Fhir\Transformers\MedicationRequestToFhirTransformer::class,
            'search_params' => ['patient', 'status', 'intent'],
        ],
        'ServiceRequest' => [
            'model' => App\Models\ServiceRequest::class,
            'transformer' => App\Services\Fhir\Transformers\ServiceRequestToFhirTransformer::class,
            'search_params' => ['patient', 'status', 'intent'],
        ],
        'AllergyIntolerance' => [
            'model' => App\Models\AllergyIntolerance::class,
            'transformer' => App\Services\Fhir\Transformers\AllergyIntoleranceToFhirTransformer::class,
            'search_params' => ['patient', 'clinical-status', 'category'],
        ],
        'Practitioner' => [
            'model' => App\Models\User::class,
            'transformer' => App\Services\Fhir\Transformers\PractitionerToFhirTransformer::class,
            'search_params' => ['name', 'identifier'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Settings
    |--------------------------------------------------------------------------
    |
    | Configure default and maximum page sizes for FHIR search operations.
    | These limits protect against performance issues with large result sets.
    |
    */
    'max_page_size' => env('FHIR_MAX_PAGE_SIZE', 100),
    'default_page_size' => env('FHIR_DEFAULT_PAGE_SIZE', 20),

    /*
    |--------------------------------------------------------------------------
    | Supported Formats
    |--------------------------------------------------------------------------
    |
    | List of response formats supported by the FHIR API.
    | JSON is required by FHIR spec. XML is optional.
    |
    */
    'formats' => ['json', 'xml'],
    'default_format' => 'json',

    /*
    |--------------------------------------------------------------------------
    | Validation Settings
    |--------------------------------------------------------------------------
    |
    | Configure FHIR resource validation behavior.
    | - enable_schema_validation: Validate against FHIR R4 schema
    | - enable_business_rules: Validate Colombian-specific business rules
    |
    */
    'validation' => [
        'enable_schema_validation' => env('FHIR_ENABLE_SCHEMA_VALIDATION', true),
        'enable_business_rules' => env('FHIR_ENABLE_BUSINESS_RULES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limits for FHIR API requests.
    | Format: max_requests per decay_minutes
    |
    */
    'rate_limit' => [
        'max_requests' => env('FHIR_RATE_LIMIT_MAX', 100),
        'decay_minutes' => env('FHIR_RATE_LIMIT_DECAY', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Settings
    |--------------------------------------------------------------------------
    |
    | Configure audit logging for FHIR API requests.
    | Uses Spatie Activity Log package.
    |
    */
    'audit' => [
        'enabled' => env('FHIR_AUDIT_ENABLED', true),
        'log_name' => 'fhir-api',
    ],

    /*
    |--------------------------------------------------------------------------
    | RDA (Resumen Digital de Atención) Settings
    |--------------------------------------------------------------------------
    |
    | Configure RDA generation according to Resolución 1888 de 2025.
    |
    */
    'rda' => [
        'enabled' => env('FHIR_RDA_ENABLED', true),
        'composition_type' => [
            'system' => 'http://hl7.org/fhir/composition-type',
            'code' => '11488-4',
            'display' => 'Consult note',
        ],
    ],
];
