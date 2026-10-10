# Design: Sistema de Historias Clínicas Electrónicas (HCE) - Frontend y Flujo Clínico

## Technical Approach

Implementar la capa de presentación y workflows clínicos sobre el backend FHIR R4 existente utilizando **Livewire 3** para componentes reactivos, **Spatie Permission** para RBAC, y **DomPDF** para generación de RDA en PDF. La arquitectura sigue el patrón MVC de Laravel con Livewire actuando como controladores de presentación reactivos.

El diseño aprovecha los transformers y servicios FHIR ya implementados (`FhirTransformerService`, `RdaGeneratorService`, `FhirValidatorService`) para mantener consistencia entre la API FHIR y la UI web.

## Architecture Decisions

### Decision: Livewire 3 para UI Reactiva

**Choice**: Livewire 3 + TailwindCSS
**Alternatives considered**: 
- Vue.js/React SPA (más complejo, requiere API separada)
- Inertia.js (buena opción pero menos adecuado para formularios clínicos complejos)
- Blade puro con AJAX (sin reactividad, UX deficiente)

**Rationale**: Livewire 3 proporciona reactividad sin JavaScript complejo, ideal para formularios clínicos con múltiples pasos y validaciones en tiempo real. Los componentes Livewire pueden invocar directamente los servicios FHIR existentes sin crear capas adicionales.

### Decision: Spatie Permission para RBAC

**Choice**: `spatie/laravel-permission` v6.x
**Alternatives considered**:
- Implementación custom (mantenimiento costoso)
- Laravel Gates/Policies solos (sin gestión de roles dinámicos)
- Bouncer (menos popular, menos documentación)

**Rationale**: Spatie Permission es el estándar de facto en Laravel para RBAC. Proporciona roles, permisos, y caching out-of-the-box. Los permisos se alinean con recursos FHIR (`patients.view`, `encounters.create`, `medications.prescribe`).

### Decision: DomPDF para Generación de RDA

**Choice**: `barryvdh/laravel-dompdf`
**Alternatives considered**:
- wkhtmltopdf (dependencia externa, configuración compleja)
- Snappy (wrapper de wkhtmltopdf)
- TCPDF (API antigua, menos features)

**Rationale**: DomPDF es puro PHP, sin dependencias externas, y se integra nativamente con Laravel. Suficiente para RDA que son documentos estructurados con tablas y texto.

### Decision: Arquitectura de Componentes Livewire por Dominio

**Choice**: Un componente Livewire principal por entidad clínica con sub-componentes para formularios multi-paso

**Rationale**: Siguiendo el patrón existente de controladores FHIR (`FhirPatientController`, `FhirEncounterController`), los componentes Livewire se organizan por dominio:
- `Patients/Index` - Búsqueda y listado
- `Patients/Create` - Formulario de creación
- `Patients/Show` - Vista detallada
- `Encounters/Create` - Flujo clínico multi-paso
- `Encounters/Show` - Resumen del encuentro

### Decision: Observers para Audit Logging Automático

**Choice**: Eloquent Observers con Spatie ActivityLog
**Alternatives considered**:
- Logging manual en cada controlador (propenso a errores)
- Database triggers (fuera del control de la aplicación)

**Rationale**: Los Observers capturan automáticamente todos los eventos de modelo (created, updated, deleted) y generan logs de auditoría consistentes. Ya existe `FhirAuditMiddleware` para la API; los Observers extienden esto a la UI web.

### Decision: Encrypted Cast para Datos Sensibles

**Choice**: Custom Cast `EncryptedCast` usando `encrypt()`/`decrypt()` de Laravel
**Alternatives considered**:
- Columnas encriptadas a nivel de base de datos (PostgreSQL pgcrypto)
- Librerías externas de encriptación

**Rationale**: Laravel's `encrypt()` usa AES-256-GCM con clave de aplicación. Es transparente para el código y cumple con Ley 1581. Se aplica a campos como `email`, `phone`, `fingerprint_template`.

## Data Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         BROWSER (Livewire)                               │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐                   │
│  │ Patient/Index│  │Encounter/Form│  │ RDA/PDF      │                   │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘                   │
└─────────┼─────────────────┼─────────────────┼───────────────────────────┘
          │                 │                 │
          │ HTTP/WebSocket  │                 │
          ▼                 ▼                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                      LARAVEL APPLICATION                                 │
│                                                                          │
│  ┌─────────────┐    ┌─────────────────┐    ┌──────────────────────┐    │
│  │  Livewire   │───▶│ FHIR Services   │───▶│ Eloquent Models      │    │
│  │ Components  │    │ (Transformer,   │    │ (Patient, Encounter, │    │
│  │             │    │  Validator,     │    │  Observation, etc.)  │    │
│  │             │    │  RDA Generator) │    │                      │    │
│  └─────────────┘    └─────────────────┘    └──────────┬───────────┘    │
│         │                                           │                  │
│         │              ┌────────────────┐           │                  │
│         └─────────────▶│   Observers    │◀──────────┘                  │
│                        │ (Audit Logging)│                              │
│                        └───────┬────────┘                              │
│                                │                                        │
│  ┌─────────────┐    ┌─────────▼────────┐    ┌──────────────────────┐   │
│  │ Middleware  │    │ Activity Log     │    │ Permission/Role      │   │
│  │ (Auth, Rate │    │ (Spatie)         │    │ (Spatie Permission)  │   │
│  │  Limiting)  │    │                  │    │                      │   │
│  └─────────────┘    └──────────────────┘    └──────────────────────┘   │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
          │
          ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                      POSTGRESQL DATABASE                                 │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐                 │
│  │ patients │  │encounters│  │conditions│  │activity_log│               │
│  │ (JSONB)  │  │ (JSONB)  │  │ (JSONB)  │  │            │               │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘                 │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐                 │
│  │model_has │  │role_has_ │  │  roles   │  │permissions│                │
│  │ _roles   │  │permissions│ │          │  │            │               │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘                 │
└─────────────────────────────────────────────────────────────────────────┘
```

### Flujo de Creación de Encuentro

```
User Input (Livewire)
       │
       ▼
┌──────────────────────┐
│ Encounter/Create     │
│ (Multi-step Form)    │
│ - Step 1: Patient    │
│ - Step 2: Reason     │
│ - Step 3: Vitals     │
│ - Step 4: Diagnosis  │
│ - Step 5: Treatment  │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│ FhirTransformerService│
│ - transform()         │
│ - transformToBundle() │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│ Eloquent Models      │
│ - Encounter::create()│
│ - Observation::create()│
│ - Condition::create()│
│ - MedicationRequest  │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│ Observer Events      │
│ - created()          │
│ - activity()->log()  │
└──────────────────────┘
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `composer.json` | Modify | Add: `livewire/livewire:^3.0`, `spatie/laravel-permission:^6.0`, `barryvdh/laravel-dompdf:^2.0` |
| `app/Providers/AppServiceProvider.php` | Modify | Register Observers for audit logging |
| `app/Models/User.php` | Modify | Add `HasRoles` trait from Spatie Permission |
| `app/Models/Patient.php` | Modify | Add `EncryptedCast` for phone, email |
| `app/Casts/EncryptedCast.php` | Create | Custom cast for AES-256 encryption of sensitive fields |
| `app/Rules/ValidColombianDocument.php` | Create | Validation rule for Colombian document formats |
| `app/Observers/PatientObserver.php` | Create | Audit logging for Patient CRUD operations |
| `app/Observers/EncounterObserver.php` | Create | Audit logging for Encounter operations |
| `app/Observers/ConditionObserver.php` | Create | Audit logging for Condition operations |
| `app/Observers/MedicationRequestObserver.php` | Create | Audit logging for prescriptions |
| `app/Services/Clinical/DrugInteractionService.php` | Create | Check drug-drug interactions |
| `app/Services/Clinical/AllergyCheckService.php` | Create | Validate allergies before prescriptions |
| `app/Services/Clinical/ReferenceRangeService.php` | Create | Calculate reference ranges by age/gender |
| `app/Services/Biometric/BiometricService.php` | Create | Fingerprint capture and matching (Phase 5) |
| `app/Livewire/Auth/Login.php` | Create | Login component with rate limiting |
| `app/Livewire/Auth/Logout.php` | Create | Logout with audit logging |
| `app/Livewire/Dashboard/Index.php` | Create | Medical dashboard |
| `app/Livewire/Patients/Index.php` | Create | Patient search and listing |
| `app/Livewire/Patients/Create.php` | Create | Patient creation form |
| `app/Livewire/Patients/Show.php` | Create | Patient detail view |
| `app/Livewire/Patients/Edit.php` | Create | Patient edit form |
| `app/Livewire/Encounters/Create.php` | Create | Multi-step encounter form |
| `app/Livewire/Encounters/Show.php` | Create | Encounter summary view |
| `app/Livewire/Encounters/Steps/PatientSelection.php` | Create | Step 1: Select patient |
| `app/Livewire/Encounters/Steps/ReasonForVisit.php` | Create | Step 2: Reason for visit |
| `app/Livewire/Encounters/Steps/VitalSigns.php` | Create | Step 3: Record vital signs |
| `app/Livewire/Encounters/Steps/Diagnosis.php` | Create | Step 4: Add diagnoses |
| `app/Livewire/Encounters/Steps/Treatment.php` | Create | Step 5: Prescribe medications |
| `app/Livewire/Conditions/Form.php` | Create | Diagnosis form with CIE-10 search |
| `app/Livewire/Medications/Form.php` | Create | Prescription form with allergy check |
| `app/Livewire/Laboratory/Form.php` | Create | Lab order form with LOINC search |
| `app/Livewire/Rda/Generate.php` | Create | RDA PDF generation |
| `config/livewire.php` | Create | Livewire configuration |
| `config/permission.php` | Create | Spatie Permission configuration |
| `config/dompdf.php` | Create | DomPDF configuration |
| `config/hce.php` | Create | HCE-specific configuration (session timeout, rate limits) |
| `database/migrations/xxxx_create_permission_tables.php` | Create | Migration for Spatie Permission tables |
| `database/migrations/xxxx_add_role_to_users_table.php` | Create | Add role column to users |
| `database/migrations/xxxx_create_patients_fingerprint_table.php` | Create | Fingerprint storage for biometric |
| `database/seeders/RolePermissionSeeder.php` | Create | Seed roles and permissions |
| `database/seeders/UserSeeder.php` | Create | Create default admin user |
| `resources/views/layouts/app.blade.php` | Create | Main layout with sidebar |
| `resources/views/layouts/auth.blade.php` | Create | Authentication layout |
| `resources/views/livewire/auth/login.blade.php` | Create | Login form view |
| `resources/views/livewire/dashboard/index.blade.php` | Create | Dashboard view |
| `resources/views/livewire/patients/index.blade.php` | Create | Patient list view |
| `resources/views/livewire/patients/create.blade.php` | Create | Patient creation form |
| `resources/views/livewire/patients/show.blade.php` | Create | Patient detail view |
| `resources/views/livewire/patients/edit.blade.php` | Create | Patient edit form |
| `resources/views/livewire/encounters/create.blade.php` | Create | Multi-step encounter form |
| `resources/views/livewire/encounters/show.blade.php` | Create | Encounter summary |
| `resources/views/components/patient-card.blade.php` | Create | Reusable patient card component |
| `resources/views/components/vital-signs-input.blade.php` | Create | Vital signs input component |
| `resources/views/components/alert.blade.php` | Create | Alert/notification component |
| `resources/views/components/modal.blade.php` | Create | Modal dialog component |
| `resources/views/rda/pdf.blade.php` | Create | RDA PDF template |
| `routes/web.php` | Modify | Add web routes for Livewire components |
| `tests/Feature/Livewire/Patients/IndexTest.php` | Create | Test patient search |
| `tests/Feature/Livewire/Patients/CreateTest.php` | Create | Test patient creation |
| `tests/Feature/Livewire/Encounters/CreateTest.php` | Create | Test encounter workflow |
| `tests/Unit/Rules/ValidColombianDocumentTest.php` | Create | Test document validation |
| `tests/Unit/Services/DrugInteractionServiceTest.php` | Create | Test drug interactions |
| `tests/Unit/Services/AllergyCheckServiceTest.php` | Create | Test allergy checking |

## Interfaces / Contracts

### ColombianDocumentValidator Interface

```php
<?php

namespace App\Contracts;

interface ColombianDocumentValidator
{
    /**
     * Validate a Colombian document number.
     *
     * @param string $documentNumber
     * @param string $type Document type: 'CC', 'TI', 'PA', 'RC'
     * @return bool
     */
    public function validate(string $documentNumber, string $type = 'CC'): bool;

    /**
     * Detect document type from number.
     *
     * @param string $documentNumber
     * @return string|null Document type or null if unknown
     */
    public function detectType(string $documentNumber): ?string;
}
```

### VitalSignsNormalizer Interface

```php
<?php

namespace App\Contracts;

interface VitalSignsNormalizer
{
    /**
     * Normalize vital sign value to standard units.
     *
     * @param float $value
     * @param string $fromUnit
     * @param string $toUnit
     * @return float
     */
    public function normalize(float $value, string $fromUnit, string $toUnit): float;

    /**
     * Get reference range for vital sign.
     *
     * @param string $loincCode
     * @param int $age
     * @param string $gender
     * @return array{low: float, high: float, unit: string}
     */
    public function getReferenceRange(string $loincCode, int $age, string $gender): array;

    /**
     * Interpret vital sign value.
     *
     * @param float $value
     * @param string $loincCode
     * @param int $age
     * @param string $gender
     * @return string 'low'|'normal'|'high'|'critical-low'|'critical-high'
     */
    public function interpret(float $value, string $loincCode, int $age, string $gender): string;
}
```

### DrugInteractionChecker Interface

```php
<?php

namespace App\Contracts;

interface DrugInteractionChecker
{
    /**
     * Check for drug interactions.
     *
     * @param array $medications Array of medication codes (RxNorm)
     * @return array Array of interactions with severity
     */
    public function checkInteractions(array $medications): array;

    /**
     * Get interaction description.
     *
     * @param string $drug1 RxNorm code
     * @param string $drug2 RxNorm code
     * @return array|null Interaction details or null if no interaction
     */
    public function getInteraction(string $drug1, string $drug2): ?array;
}
```

### AllergyChecker Interface

```php
<?php

namespace App\Contracts;

use App\Models\Patient;

interface AllergyChecker
{
    /**
     * Check if patient is allergic to medication.
     *
     * @param Patient $patient
     * @param string $medicationCode RxNorm code
     * @return array{allergic: bool, allergies: array}
     */
    public function check(Patient $patient, string $medicationCode): array;

    /**
     * Get all allergies for a patient.
     *
     * @param Patient $patient
     * @return array Array of AllergyIntolerance data
     */
    public function getAllergies(Patient $patient): array;
}
```

### EncryptedCast Implementation

```php
<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class EncryptedCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function get($model, string $key, $value, array $attributes)
    {
        return $value ? decrypt($value) : null;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($model, string $key, $value, array $attributes)
    {
        return $value ? encrypt($value) : null;
    }
}
```

### ValidColombianDocument Rule

```php
<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidColombianDocument implements Rule
{
    protected string $documentType;

    public function __construct(string $documentType = null)
    {
        $this->documentType = $documentType;
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value): bool
    {
        $value = trim($value);

        // Cédula de Ciudadanía (8-10 digits)
        if (preg_match('/^\d{8,10}$/', $value)) {
            return $this->documentType === null || $this->documentType === 'CC';
        }

        // Tarjeta de Identidad (format with hyphen)
        if (preg_match('/^\d{6,9}-\d{1,2}$/', $value)) {
            return $this->documentType === null || $this->documentType === 'TI';
        }

        // Pasaporte (letters and numbers, 6-12 chars)
        if (preg_match('/^[A-Z0-9]{6,12}$/i', $value)) {
            return $this->documentType === null || $this->documentType === 'PA';
        }

        // Registro Civil (starts with RC)
        if (preg_match('/^RC\d{6,10}$/i', $value)) {
            return $this->documentType === null || $this->documentType === 'RC';
        }

        return false;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'El número de documento no tiene un formato válido colombiano.';
    }
}
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| **Unit** | `ValidColombianDocument` rule | Test all valid/invalid document formats |
| **Unit** | `EncryptedCast` | Test encryption/decryption round-trip |
| **Unit** | `ReferenceRangeService` | Test reference range calculations by age/gender |
| **Unit** | `DrugInteractionService` | Test known drug interactions |
| **Unit** | `AllergyCheckService` | Test allergy detection logic |
| **Unit** | Observers | Test audit log generation on model events |
| **Integration** | Livewire `Patients/Index` | Test patient search functionality |
| **Integration** | Livewire `Patients/Create` | Test patient creation with validation |
| **Integration** | Livewire `Encounters/Create` | Test multi-step encounter workflow |
| **Integration** | RBAC permissions | Test role-based access restrictions |
| **Integration** | Login rate limiting | Test lockout after 5 failed attempts |
| **Integration** | Session timeout | Test automatic logout after 15 min |
| **Feature** | Full encounter flow | Create patient → encounter → diagnosis → prescription |
| **Feature** | RDA PDF generation | Test PDF contains all required elements |
| **Feature** | Audit trail | Verify all operations logged correctly |

### Test Coverage Targets

| Component | Target Coverage |
|-----------|-----------------|
| Rules | 100% |
| Casts | 100% |
| Services | 85% |
| Observers | 90% |
| Livewire Components | 80% |
| Integration tests | All critical paths covered |

## Migration / Rollout

### Phase 1: Authentication & Security (Week 1-2)

```bash
# Install dependencies
composer require livewire/livewire:^3.0
composer require spatie/laravel-permission:^6.0

# Run migrations
php artisan migrate

# Seed roles and permissions
php artisan db:seed --class=RolePermissionSeeder

# Feature flags (optional)
# HCE_RATES_LIMIT_ENABLED=true
# HCE_SESSION_TIMEOUT=900
```

### Phase 2: Patient Management (Week 3-4)

```bash
# No migration required - uses existing patients table
# Add encrypted columns migration if needed
php artisan make:migration add_encrypted_fields_to_patients_table
```

### Phase 3: Clinical Workflow (Week 5-8)

```bash
# Install DomPDF
composer require barryvdh/laravel-dompdf:^2.0

# Publish config
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

### Phase 4: Compliance & RDA (Week 9-10)

```bash
# Feature flags for encryption
# HCE_ENCRYPTION_ENABLED=true
```

### Phase 5: Biometric (Week 11-12) - Optional

```bash
# Migration for fingerprint storage
php artisan make:migration create_patient_fingerprints_table

# Feature flag
# HCE_BIOMETRIC_ENABLED=false
```

### Rollback Plan

```bash
# Full rollback
git revert HEAD~N  # Revert commits

# Disable features via .env
HCE_BIOMETRIC_ENABLED=false
HCE_ENCRYPTION_ENABLED=false

# Database rollback
php artisan migrate:rollback --step=5
```

## Open Questions

- [ ] **Drug interaction database source**: ¿Usar base de datos local (SQLite) o API externa para interacciones medicamentosas? Recomendación: SQLite local con actualizaciones periódicas.
- [ ] **CIE-10/SNOMED terminology service**: ¿Implementar servidor de terminología local o usar API externa? Recomendación: Archivos JSON locales para códigos más comunes.
- [ ] **Biometric device selection**: ¿Qué dispositivo biométrico usar? DigitalPersona vs Suprema. Depende de disponibilidad y presupuesto.
- [ ] **PDF template design**: ¿Quién diseña la plantilla del RDA? Requiere aprobación legal. Pendiente definición con Vespot.
- [ ] **Session storage**: ¿Usar Redis para sesiones en producción? Recomendación: Sí, para escalabilidad.
- [ ] **Audit log retention**: ¿Confirmar retención de 20 años? Requiere definición de política de archivo histórico.