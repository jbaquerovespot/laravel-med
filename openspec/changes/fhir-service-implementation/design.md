# Design: Implementación de Servicio FHIR R4 para Vespot HCE

## Technical Approach

Implementación de API FHIR R4 REST siguiendo arquitectura en capas de Laravel con separación clara de responsabilidades:

- **Middleware Layer**: Autenticación (Sanctum), auditoría (Spatie), rate limiting, content negotiation
- **Controller Layer**: 8 controladores FHIR con operaciones CRUD estandarizadas
- **Service Layer**: Transformadores, validadores, RDA generator, search service
- **Model Layer**: Eloquent models con almacenamiento dual (tablas relacionales + JSONB)
- **Storage Layer**: PostgreSQL con índices GIN para búsquedas en JSON

Cada recurso FHIR tendrá su transformer bidireccional que convierte entre modelo Laravel y recurso FHIR, validador específico, y controlador con endpoints estándar.

## Architecture Decisions

### Decision: Almacenamiento Dual (Relacional + JSONB)

**Choice**: Almacenar datos en tablas relacionales normales Y en columna JSONB `fhir_resource`

**Alternatives considered**:
1. Solo tablas relacionales - reconstructir FHIR en cada request
2. Solo JSONB - todo el documento FHIR en una columna
3. Base de datos FHIR dedicada (HAPI FHIR Server)

**Rationale**: 
- **Dual** ofrece lo mejor de ambos mundos: consultas SQL eficientes + flexibilidad de JSON
- Columna JSONB permite almacenar recurso FHIR completo para respuestas rápidas
- Tablas relacionales permiten búsquedas complejas, foreign keys, integridad referencial
- Índices GIN en JSONB habilitan búsquedas full-text en contenido FHIR
- Compatible con PostgreSQL (ya usado en proyecto)
- Evita dependencia de servidor FHIR externo
- Migración gradual posible (primero relacional, luego JSONB)

### Decision: Transformadores por Clase (Strategy Pattern)

**Choice**: Una clase transformer por recurso FHIR implementando interface común

**Alternatives considered**:
1. Un transformer monolítico con switch por tipo
2. Traits reutilizables
3. Librería externa de transformación

**Rationale**:
- **Strategy pattern** permite especialización por recurso sin acoplamiento
- Cada transformer conoce mapeo específico de su recurso
- Interface común garantiza consistencia en methods: `transform()` y `transformFromFhir()`
- Fácil de testear unitariamente
- Extensible: nuevo recurso = nuevo transformer, sin modificar existentes (Open/Closed)
- Sigue patrón ya establecido en spec de referencia

### Decision: Validación en Tres Niveles

**Choice**: Validación progresiva: estructura → schema FHIR → reglas de negocio

**Alternatives considered**:
1. Solo validación de schema oficial
2. Solo reglas de negocio
3. Validación externa con servicio HAPI FHIR

**Rationale**:
- **Tres niveles** captura errores temprano y reduce costo de validación
- **Nivel 1 (estructura)**: Rápido, verifica campos requeridos básicos
- **Nivel 2 (schema)**: Usa validador oficial FHIR R4 para conformidad
- **Nivel 3 (negocio)**: Reglas específicas de Colombia (cédula, RDA, etc.)
- Fallar rápido en nivel 1 evita llamadas costosas a validador de schema
- Reglas de negocio capturan requisitos locales no cubiertos por FHIR

### Decision: Autenticación con Laravel Sanctum

**Choice**: Laravel Sanctum con tokens de API + abilities por recurso

**Alternatives considered**:
1. Laravel Passport (OAuth2 completo)
2. JWT personalizado
3. API keys simples

**Rationale**:
- **Sanctum** es suficiente para APIs SPA/mobile sin complejidad OAuth2
- Tokens con abilities permiten permisos granulares: `fhir:patient:view`, `fhir:observation:create`
- Ligero, mantenido por Laravel core team
- Sin overhead de OAuth2 flows para caso de uso interno
- Compatible con middleware existente de Laravel
- Soporte nativo para rate limiting

### Decision: Auditoría con Spatie Activity Log

**Choice**: Spatie Activity Log para todos los requests FHIR

**Alternatives considered**:
1. Tabla de auditoría custom
2. PostgreSQL pg_audit extension
3. Servicio externo de logging

**Rationale**:
- **Spatie** es estándar de la industria en ecosistema Laravel
- Integración transparente con Eloquent
- Soporte para causer, properties, batch operations
- Querying flexible de logs
- Menos mantenimiento que solución custom
- `pg_audit` es más complejo de configurar y query

### Decision: Content Negotiation vía Middleware

**Choice**: Middleware dedicado para negotiar JSON/XML basado en header Accept

**Alternatives considered**:
1. Content negotiation en cada controlador
2. Forzar solo JSON (FHIR default)
3. Parámetro `_format` en URL

**Rationale**:
- **Middleware** centraliza lógica, controladores se mantienen limpios
- Separa cross-cutting concern de lógica de negocio
- Permite agregar formatos futuros (Turtle) sin tocar controladores
- Respeta spec FHIR que requiere content negotiation
- Default a `application/fhir+json` si no se especifica

### Decision: Búsquedas con Query Builder + Parámetros FHIR

**Choice**: Laravel Query Builder mapeando parámetros FHIR a columnas SQL

**Alternatives considered**:
1. Elasticsearch para búsquedas
2. PostgreSQL full-text search
3. FHIR Search Service dedicado

**Rationale**:
- **Query Builder** es suficiente para parámetros FHIR estándar
- Mapeo directo: `?birthdate=1985-05-15` → `where('birth_date', '1985-05-15')`
- Sin infraestructura adicional (Elasticsearch)
- Performance aceptable con índices apropiados
- Si búsquedas se vuelven complejas, migrar a Elasticsearch es posible

### Decision: OperaciónOutcome para Errores

**Choice**: Retornar `OperationOutcome` FHIR para todos los errores

**Alternatives considered**:
1. Respuestas de error JSON estándar de Laravel
2. Problema RFC 7807
3. OperaciónOutcome solo para errores de validación

**Rationale**:
- **OperationOutcome** es estándar FHIR para errores
- Clientes FHIR esperan este formato
- Proporciona estructura consistente para debugging
- Campos: severity, code, details permiten diagnóstico claro
- Cumple con FHIR REST API guidelines

## Data Flow

### Flujo de Request FHIR (GET /api/fhir/Patient?identifier=123)

```
┌──────────────┐
│   Cliente    │
│  (Frontend/  │
│   Externo)   │
└──────┬───────┘
       │ GET /api/fhir/Patient?identifier=123
       │ Headers: Authorization: Bearer {token}
       │          Accept: application/fhir+json
       ▼
┌─────────────────────────────────────────────────┐
│           Middleware Stack                       │
│  ┌─────────────────────────────────────────┐    │
│  │ FhirAuthMiddleware                       │    │
│  │ • Valida token con Sanctum              │    │
│  │ • Verifica permisos por ability         │    │
│  │ • Reject 401/403 si falla               │    │
│  └─────────────────────────────────────────┘    │
│  ┌─────────────────────────────────────────┐    │
│  │ FhirRateLimiter                          │    │
│  │ • Rate limit por usuario/IP             │    │
│  └─────────────────────────────────────────┘    │
│  ┌─────────────────────────────────────────┐    │
│  │ FhirContentNegotiation                   │    │
│  │ • Parsea Accept header                  │    │
│  │ • Set _format = json/xml                │    │
│  └─────────────────────────────────────────┘    │
└──────────────┬──────────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────────┐
│     FhirPatientController::search()             │
│  • Extrae query params (identifier, name, etc) │
│  • Invoca FhirSearchService                     │
└──────────────┬──────────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────────┐
│     FhirSearchService::search()                 │
│  • Construye query dinámico                    │
│  • Aplica filtros al Patient model             │
│  • Ejecuta query con paginación                │
└──────────────┬──────────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────────┐
│     Patient Model (Eloquent)                    │
│  • Query a PostgreSQL                          │
│  • Retorna Collection de Patient               │
└──────────────┬──────────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────────┐
│     FhirTransformerService                      │
│  • Itera sobre Collection                      │
│  • Invoca PatientToFhirTransformer::transform()│
│  • Construye Bundle FHIR con entries           │
└──────────────┬──────────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────────┐
│     FhirValidatorService                        │
│  • Valida Bundle contra schema FHIR            │
│  • Valida reglas de negocio                    │
└──────────────┬──────────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────────┐
│     Response JSON                               │
│  { resourceType: "Bundle",                     │
│    type: "searchset",                          │
│    entry: [...] }                              │
│  Headers: Content-Type: application/fhir+json  │
└──────────────┬──────────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────────┐
│     FhirAuditMiddleware (afterResponse)         │
│  • Log de auditoría con:                       │
│    - Usuario, IP, método, URL                  │
│    - Resource type, status code, duración      │
└──────────────┬──────────────────────────────────┘
               │
               ▼
┌──────────────┐
│   Cliente    │
│  recibe      │
│  Bundle FHIR │
└──────────────┘
```

### Flujo de Creación (POST /api/fhir/Patient)

```
Cliente → POST {FHIR JSON}
   │
   ▼
Middleware (auth, rate limit)
   │
   ▼
FhirPatientController::store()
   │
   ▼
FhirValidatorService::validate(fhirResource, 'Patient')
   ├─→ Nivel 1: Estructura (campos requeridos)
   ├─→ Nivel 2: Schema FHIR R4
   └─→ Nivel 3: Reglas de negocio (cédula, birthDate, etc.)
   │
   ├─❌ Si falla → OperationOutcome (400)
   │
   ▼ (si pasa validación)
FhirTransformerService::transformFromFhir(fhirResource, 'Patient')
   │
   ▼
PatientToFhirTransformer::transformFromFhir()
   │
   ▼
Patient model instanciado con datos
   │
   ▼
Patient::save() → PostgreSQL
   │
   ▼
FhirTransformerService::transform(patient, 'Patient')
   │
   ▼
PatientToFhirTransformer::transform()
   │
   ▼
FHIR JSON resource
   │
   ▼
Response (201) + Location header
   │
   ▼
FhirAuditMiddleware::log()
```

### Flujo de Generación RDA

```
Cliente → GET /api/fhir/$rda?patient_id=123&encounter_id=456
   │
   ▼
FhirBundleController::generateRda()
   │
   ▼
RdaGeneratorService::generateRda(patientId, encounterId)
   │
   ├─→ Carga Patient (transforma a FHIR)
   ├─→ Carga Encounter (transforma a FHIR)
   ├─→ Carga Conditions del encounter (transforma a FHIR)
   ├─→ Carga Observations del encounter (transforma a FHIR)
   ├─→ Carga MedicationRequests del encounter (transforma a FHIR)
   ├─→ Carga ServiceRequests del encounter (transforma a FHIR)
   ├─→ Carga AllergyIntolerance del patient (transforma a FHIR)
   │
   ▼
Construye Bundle FHIR tipo "document"
   │
   ├─ resourceType: Bundle
   ├─ type: document
   ├─ identifier: UUID único
   ├─ timestamp: now()
   ├─ entry[0]: Composition (RDA composition)
   ├─ entry[1..n]: Resources clínicos
   │
   ▼
Valida Bundle completo
   │
   ▼
Retorna Bundle FHIR (200)
```

## File Changes

### New Files

| File | Description |
|------|-------------|
| `app/Http/Controllers/Fhir/FhirPatientController.php` | CRUD operations para Patient FHIR |
| `app/Http/Controllers/Fhir/FhirEncounterController.php` | CRUD operations para Encounter FHIR |
| `app/Http/Controllers/Fhir/FhirObservationController.php` | CRUD operations para Observation FHIR |
| `app/Http/Controllers/Fhir/FhirConditionController.php` | CRUD operations para Condition FHIR |
| `app/Http/Controllers/Fhir/FhirMedicationRequestController.php` | CRUD operations para MedicationRequest FHIR |
| `app/Http/Controllers/Fhir/FhirServiceRequestController.php` | CRUD operations para ServiceRequest FHIR |
| `app/Http/Controllers/Fhir/FhirAllergyIntoleranceController.php` | CRUD operations para AllergyIntolerance FHIR |
| `app/Http/Controllers/Fhir/FhirPractitionerController.php` | CRUD operations para Practitioner FHIR |
| `app/Http/Controllers/Fhir/FhirBundleController.php` | Generación de Bundle RDA y operaciones compuestas |
| `app/Services/Fhir/FhirTransformerService.php` | Servicio central de transformación bidireccional |
| `app/Services/Fhir/FhirValidatorService.php` | Validación en 3 niveles (estructura, schema, negocio) |
| `app/Services/Fhir/RdaGeneratorService.php` | Generación de Bundle RDA |
| `app/Services/Fhir/FhirSearchService.php` | Búsquedas FHIR con parámetros estándar |
| `app/Services/Fhir/Transformers/FhirTransformerInterface.php` | Interface común para todos los transformers |
| `app/Services/Fhir/Transformers/PatientToFhirTransformer.php` | Transformación específica de Patient |
| `app/Services/Fhir/Transformers/EncounterToFhirTransformer.php` | Transformación específica de Encounter |
| `app/Services/Fhir/Transformers/ObservationToFhirTransformer.php` | Transformación específica de Observation |
| `app/Services/Fhir/Transformers/ConditionToFhirTransformer.php` | Transformación específica de Condition |
| `app/Services/Fhir/Transformers/MedicationRequestToFhirTransformer.php` | Transformación específica de MedicationRequest |
| `app/Services/Fhir/Transformers/ServiceRequestToFhirTransformer.php` | Transformación específica de ServiceRequest |
| `app/Services/Fhir/Transformers/AllergyIntoleranceToFhirTransformer.php` | Transformación específica de AllergyIntolerance |
| `app/Services/Fhir/Transformers/PractitionerToFhirTransformer.php` | Transformación específica de Practitioner |
| `app/Http/Middleware/FhirAuthMiddleware.php` | Autenticación y autorización para APIs FHIR |
| `app/Http/Middleware/FhirAuditMiddleware.php` | Auditoría de todos los requests FHIR |
| `app/Http/Middleware/FhirContentNegotiationMiddleware.php` | Content negotiation (JSON/XML) |
| `app/Http/Middleware/FhirRateLimiter.php` | Rate limiting específico para FHIR |
| `app/Http/Responses/Fhir/OperationOutcomeResponse.php` | Response wrapper para errores FHIR |
| `routes/fhir.php` | Archivo de rutas específico para endpoints FHIR |
| `config/fhir.php` | Configuración del servicio FHIR (habilitado, formatos, límites) |
| `tests/Feature/Fhir/FhirPatientTest.php` | Tests de integración para Patient endpoints |
| `tests/Feature/Fhir/FhirEncounterTest.php` | Tests de integración para Encounter endpoints |
| `tests/Feature/Fhir/FhirObservationTest.php` | Tests de integración para Observation endpoints |
| `tests/Feature/Fhir/FhirAuthTest.php` | Tests de autenticación y permisos |
| `tests/Feature/Fhir/FhirRdaTest.php` | Tests de generación de RDA |
| `tests/Unit/Fhir/Transformers/PatientToFhirTransformerTest.php` | Tests unitarios para Patient transformer |
| `tests/Unit/Fhir/Transformers/EncounterToFhirTransformerTest.php` | Tests unitarios para Encounter transformer |
| `tests/Unit/Fhir/Transformers/ObservationToFhirTransformerTest.php` | Tests unitarios para Observation transformer |
| `tests/Unit/Fhir/ValidatorServiceTest.php` | Tests unitarios para validador |
| `database/migrations/xxxx_xx_xx_add_fhir_resource_column_to_patients.php` | Migración para columna JSONB en patients |
| `database/migrations/xxxx_xx_xx_add_fhir_resource_column_to_encounters.php` | Migración para columna JSONB en encounters |
| `database/migrations/xxxx_xx_xx_add_fhir_resource_column_to_observations.php` | Migración para columna JSONB en observations |
| `database/migrations/xxxx_xx_xx_add_fhir_resource_column_to_conditions.php` | Migración para columna JSONB en conditions |
| `database/migrations/xxxx_xx_xx_create_fhir_audit_logs_table.php` | Tabla de auditoría específica FHIR (opcional, si no usa Spatie) |

### Modified Files

| File | Changes |
|------|---------|
| `routes/api.php` | Agregar `Route::prefix('fhir')->group(base_path('routes/fhir.php'))` |
| `app/Models/Patient.php` | Agregar columna `fhir_resource` (JSONB), casts, métodos helper |
| `app/Models/Encounter.php` | Agregar columna `fhir_resource` (JSONB), casts, métodos helper |
| `app/Models/Observation.php` | Agregar columna `fhir_resource` (JSONB), casts, métodos helper |
| `app/Models/Condition.php` | Agregar columna `fhir_resource` (JSONB), casts, métodos helper |
| `app/Models/MedicationRequest.php` | Agregar columna `fhir_resource` (JSONB), casts, métodos helper |
| `app/Models/ServiceRequest.php` | Agregar columna `fhir_resource` (JSONB), casts, métodos helper |
| `app/Models/AllergyIntolerance.php` | Agregar columna `fhir_resource` (JSONB), casts, métodos helper |
| `app/Models/User.php` | Agregar relación a Practitioner, métodos para abilities FHIR |
| `config/sanctum.php` | Configurar middleware para APIs stateful |
| `config/activitylog.php` | Configurar Activity Log para auditoría FHIR |
| `app/Http/Kernel.php` | Registrar middleware: `fhir.auth`, `fhir.audit`, `fhir.content`, `fhir.rate` |
| `.env.example` | Agregar variables: `FHIR_ENABLED=true`, `FHIR_MAX_PAGE_SIZE=100` |

### Deleted Files

- None

## Interfaces / Contracts

### FhirTransformerInterface

```php
<?php

namespace App\Services\Fhir\Transformers;

interface FhirTransformerInterface
{
    /**
     * Transformar modelo Laravel a recurso FHIR
     *
     * @param object $model Modelo Eloquent
     * @return array Recurso FHIR JSON
     */
    public function transform(object $model): array;

    /**
     * Transformar recurso FHIR a modelo Laravel
     *
     * @param array $fhirResource Recurso FHIR JSON
     * @return object Modelo Eloquent (no persistido)
     */
    public function transformFromFhir(array $fhirResource): object;
}
```

### ValidationResult

```php
<?php

namespace App\Services\Fhir;

use Illuminate\Support\Collection;

class ValidationResult
{
    public function __construct(
        public readonly bool $valid,
        public readonly Collection $errors
    ) {}

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getErrors(): array
    {
        return $this->errors->toArray();
    }

    public function getErrorMessages(): array
    {
        return $this->errors->map(fn($e) => (string) $e)->toArray();
    }
}
```

### FHIR Resource Types Soportados

```php
// config/fhir.php
return [
    'enabled' => env('FHIR_ENABLED', true),
    
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
        // ... más recursos
    ],
    
    'max_page_size' => env('FHIR_MAX_PAGE_SIZE', 100),
    'default_page_size' => 20,
    
    'formats' => ['json', 'xml'],
    'default_format' => 'json',
    
    'validation' => [
        'enable_schema_validation' => true,
        'enable_business_rules' => true,
    ],
];
```

### FHIR API Endpoints

```php
// routes/fhir.php

// Patient
Route::get('/Patient', [FhirPatientController::class, 'search']);
Route::get('/Patient/{id}', [FhirPatientController::class, 'show']);
Route::post('/Patient', [FhirPatientController::class, 'store']);
Route::put('/Patient/{id}', [FhirPatientController::class, 'update']);
Route::delete('/Patient/{id}', [FhirPatientController::class, 'destroy']);

// Encounter
Route::get('/Encounter', [FhirEncounterController::class, 'search']);
Route::get('/Encounter/{id}', [FhirEncounterController::class, 'show']);
Route::post('/Encounter', [FhirEncounterController::class, 'store']);
Route::put('/Encounter/{id}', [FhirEncounterController::class, 'update']);

// ... (similar para Observation, Condition, MedicationRequest, ServiceRequest, AllergyIntolerance, Practitioner)

// Bundle / RDA
Route::post('/Bundle', [FhirBundleController::class, 'transaction']);
Route::get('/$rda', [FhirBundleController::class, 'generateRda']);

// Metadata / CapabilityStatement
Route::get('/metadata', [FhirBundleController::class, 'capabilityStatement']);
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| **Unit** | `FhirTransformerService` | Mock transformers, verificar delegación correcta |
| **Unit** | Cada `*ToFhirTransformer::transform()` | Datos de entrada conocidos → FHIR esperado, verificar estructura, códigos, sistemas |
| **Unit** | Cada `*ToFhirTransformer::transformFromFhir()` | FHIR de entrada → modelo Laravel esperado |
| **Unit** | `FhirValidatorService::validate()` | Recursos válidos e inválidos, verificar 3 niveles |
| **Unit** | `RdaGeneratorService` | Bundle RDA con estructura correcta, todos los recursos incluidos |
| **Integration** | `FhirPatientController::search()` | GET con varios query params, verificar filters, paginación, response Bundle |
| **Integration** | `FhirPatientController::show()` | GET por ID, verificar 200/404, estructura Patient |
| **Integration** | `FhirPatientController::store()` | POST con Patient válido (201) e inválido (400), verificar Location header |
| **Integration** | `FhirPatientController::update()` | PUT con recurso válido, verificar 200, recursos actualizado |
| **Integration** | `FhirAuthMiddleware` | Request sin token (401), con token inválido (401), con permisos insuficientes (403) |
| **Integration** | `FhirAuditMiddleware` | Verificar cada request genera log en activity_log |
| **Integration** | `FhirContentNegotiationMiddleware` | Accept: application/fhir+json vs application/fhir+xml |
| **Integration** | `FhirBundleController::generateRda()` | RDA completo con Patient + Encounter + recursos clínicos |
| **E2E** | Flujo completo de atención | Crear Patient → Crear Encounter → Crear Observations → Generar RDA → Validar Bundle |

### Herramientas de Test

- **PHPUnit** para tests unitarios y de integración
- **Laravel Testing helpers** para simular requests HTTP
- **Postman collection** para pruebas manuales y automatizadas
- **HAPI FHIR Validator** para validación externa de recursos generados

### Coverage Target

- **Services**: >90% coverage (transformers, validadores)
- **Controllers**: >80% coverage (endpoints CRUD)
- **Middleware**: >85% coverage (auth, audit, content negotiation)

## Migration / Rollout

### Fase 1: Infraestructura Base (Semana 1)

```bash
# Instalar dependencias
composer require laravel/sanctum
composer require spatie/laravel-activitylog
composer require hl7/fhir  # o librería de validación FHIR

# Publicar configs
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"

# Migraciones iniciales
php artisan migrate
```

### Fase 2: Models + JSONB (Semana 2)

- Crear migraciones para columna `fhir_resource` JSONB en cada tabla
- Agregar casts y métodos helper a modelos
- Tests unitarios para modelos

### Fase 3: Transformers (Semana 3-4)

- Implementar interface `FhirTransformerInterface`
- Implementar 8 transformers uno por uno
- Tests unitarios para cada transformer (ida y vuelta)
- **Orden**: Patient → Encounter → Observation → Condition → MedicationRequest → ServiceRequest → AllergyIntolerance → Practitioner

### Fase 4: Servicios Centrales (Semana 5)

- Implementar `FhirTransformerService`
- Implementar `FhirValidatorService`
- Implementar `RdaGeneratorService`
- Implementar `FhirSearchService`
- Tests unitarios para servicios

### Fase 5: Middleware (Semana 6)

- Implementar `FhirAuthMiddleware`
- Implementar `FhirAuditMiddleware`
- Implementar `FhirContentNegotiationMiddleware`
- Implementar `FhirRateLimiter`
- Tests unitarios para middleware

### Fase 6: Controladores (Semana 7)

- Implementar 8 controladores FHIR
- Implementar `FhirBundleController`
- Tests de integración para endpoints
- Postman collection

### Fase 7: Rutas + Config (Semana 8)

- Crear `routes/fhir.php`
- Registrar middleware en Kernel
- Crear `config/fhir.php`
- Tests E2E

### Fase 8: Performance + Optimización (Semana 9)

- Agregar índices GIN en columnas JSONB
- Optimizar queries con eager loading
- Benchmark de performance
- Ajustar rate limits

### Rollout Strategy

1. **Feature flag**: `FHIR_ENABLED=false` inicialmente
2. **Deploy a staging**: Validar con datos de prueba
3. **Pruebas de conformidad**: Validar con herramientas FHIR (Touchstone, Inferno)
4. **Enable en production**: `FHIR_ENABLED=true`
5. **Monitoreo**: Laravel Telescope + logs de auditoría

### Feature Flag

```php
// .env
FHIR_ENABLED=true
FHIR_MAX_PAGE_SIZE=100

// Middleware (opcional, para deshabilitar rápidamente)
public function handle(Request $request, Closure $next)
{
    if (!config('fhir.enabled')) {
        return response()->json([
            'resourceType' => 'OperationOutcome',
            'issue' => [[
                'severity' => 'error',
                'code' => 'not-supported',
                'details' => ['text' => 'FHIR API is currently disabled']
            ]]
        ], 503);
    }
    
    return $next($request);
}
```

## Open Questions

- [ ] **¿Usar librería `hl7/fhir` o `hapi-fhir-validation`?** - Depende de disponibilidad para PHP. Si no hay buena opción PHP, considerar validación externa vía API.
- [ ] **¿Almacenar solo JSONB y eliminar columnas relacionales?** - Requiere más análisis. JSONB puro da flexibilidad pero pierde integridad referencial y queries complejas.
- [ ] **¿Incluir soporte para FHIR Subscriptions en fase 1 o dejar para después?** - Recomendación: dejar para fase 2, priorizar REST API primero.
- [ ] **¿Qué versión exacta de PostgreSQL se usa en producción?** - Índices GIN requieren PostgreSQL 9.5+. Verificar compatibilidad.
- [ ] **¿Se necesita soporte para XML desde el inicio o solo JSON?** - Spec menciona ambos, pero JSON es default. ¿Podemos hacer XML en fase 2?
- [ ] **¿Hay requisitos específicos de la Resolución 1888 para el formato del RDA?** - Necesitamos ejemplos oficiales de Bundle RDA del Ministerio de Salud.
