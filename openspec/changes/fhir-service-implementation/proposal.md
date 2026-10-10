# Proposal: Implementación de Servicio FHIR R4 para Vespot HCE

## Intent

Implementar un servicio FHIR R4 completo para el sistema de Historias Clínicas Electrónicas (HCE) de Vespot SAS, cumpliendo con:
- **Resolución 1888 de 2025** (RDA - Resumen Digital de Atención) - Colombia
- **Ley 2015 de 2020** (Interoperabilidad de Historia Clínica Electrónica)
- **HL7 FHIR R4 REST API Guidelines**

Este servicio permitirá interoperabilidad con otros sistemas de salud, cumplimiento normativo, y generación automatizada del Resumen Digital de Atención (RDA) requerido por el Ministerio de Salud.

## Scope

### In Scope
- ✅ **API FHIR REST** con 8 recursos principales (Patient, Encounter, Observation, Condition, MedicationRequest, ServiceRequest, AllergyIntolerance, Practitioner)
- ✅ **Transformadores bidireccionales** (Modelo Laravel ↔ FHIR JSON)
- ✅ **Validador FHIR R4** con validación de estructura, schema y reglas de negocio
- ✅ **Middleware de autenticación** (Laravel Sanctum + permisos por recurso)
- ✅ **Middleware de auditoría** (Spatie Activity Log)
- ✅ **Negociación de contenido** (application/fhir+json, application/fhir+xml)
- ✅ **RDA Generator** para generar Bundle FHIR con resumen de atención
- ✅ **Búsquedas FHIR avanzadas** con parámetros estándar
- ✅ **Soporte de paginación** según FHIR spec
- ✅ **OperationOutcome** para manejo de errores estandarizado

### Out of Scope
- ❌ **FHIR Subscriptions** (notificaciones en tiempo real) - fase futura
- ❌ **FHIR Messaging** (solo REST API por ahora)
- ❌ **Formato Turtle/RDF** (solo JSON y XML)
- ❌ **Terminology Service** completo (SNOMED-CT, LOINC localmente)
- ❌ **FHIR Cast** (colaboración en tiempo real)

## Approach

Implementación en capas siguiendo arquitectura Laravel:

1. **Capa de Routes/Middleware**: Autenticación, auditoría, rate limiting, content negotiation
2. **Capa de Controladores**: 8 controladores FHIR (uno por recurso) con operaciones CRUD
3. **Capa de Servicios**: 
   - `FhirTransformerService` - transformaciones bidireccionales
   - `FhirValidatorService` - validación contra schema R4 + reglas de negocio
   - `RdaGeneratorService` - generación de Bundle RDA
   - `FhirSearchService` - búsquedas con parámetros FHIR
4. **Capa de Modelos**: Eloquent models existentes (Patient, Encounter, Observation, etc.)
5. **Capa de Datos**: PostgreSQL con JSONB para almacenamiento de recursos FHIR + índices GIN

**Estrategia de implementación**:
- Usar ejemplos de código del spec como base
- Implementar transformers uno por uno (Patient → Encounter → Observation → restantes)
- Tests unitarios para cada transformer
- Tests de integración para endpoints
- Validación con POSTMAN collection

## Affected Areas

| Area | Impact | Descripción |
|------|--------|-------------|
| `app/Http/Controllers/Fhir/` | Nuevo | 8 controladores FHIR (Patient, Encounter, Observation, Condition, MedicationRequest, ServiceRequest, AllergyIntolerance, Practitioner) |
| `app/Services/Fhir/` | Nuevo | Servicios de transformación, validación, RDA, búsqueda |
| `app/Services/Fhir/Transformers/` | Nuevo | 8 transformers bidireccionales + interface |
| `app/Http/Middleware/` | Modificado | Agregar FhirAuthMiddleware, FhirAuditMiddleware, FhirContentNegotiationMiddleware, FhirRateLimiter |
| `routes/api.php` | Modificado | Rutas FHIR REST (40+ endpoints) |
| `app/Models/` | Modificado | 8 modelos Eloquent existentes ( Patient, Encounter, Observation, Condition, MedicationRequest, ServiceRequest, AllergyIntolerance, User/Practitioner) |
| `database/migrations/` | Modificado | Migraciones existentes + nueva para tabla `fhir_resources` (almacenamiento JSONB) |
| `config/sanctum.php` | Modificado | Configuración de tokens para APIs FHIR |
| `tests/Feature/Fhir/` | Nuevo | Tests de integración para endpoints FHIR |
| `tests/Unit/Fhir/` | Nuevo | Tests unitarios para transformers y validadores |
| `routes/fhir.php` | Nuevo | Archivo de rutas específico para FHIR |
| `app/Http/Responses/Fhir/` | Nuevo | Response wrappers para OperationOutcome |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| **Complejidad de validación FHIR R4** | Medium | Usar librería `hapi-fhir-validation` o `hl7/fhir` para validación oficial; implementar validación渐进ica (estructura → schema → business rules) |
| **Rendimiento en búsquedas complejas** | Medium | Índices GIN en campos JSONB; cache de búsquedas frecuentes; paginación estricta (max 100 recursos) |
| **Cumplimiento incompleto de RDA** | High | Revisar spec de Ministerio de Salud; validar con casos de prueba oficiales; usar ejemplos de Bundle RDA del spec |
| **Errores de transformación de datos** | High | Tests unitarios exhaustivos para cada transformer; validación bidireccional (ida y vuelta); logs de auditoría detallados |
| **Autenticación/permisos incorrectos** | Medium | Matriz de permisos por rol; tests de integración para cada endpoint + combinación de roles |
| **Mala performance con grandes volúmenes** | Low | Query optimization; eager loading; database indexes; monitoreo con Laravel Telescope |

## Rollback Plan

Si la implementación presenta problemas críticos:

1. **Rollback de código**: 
   ```bash
   git revert <commit-fhir-implementation>
   # O eliminar directorios específicos:
   rm -rf app/Http/Controllers/Fhir
   rm -rf app/Services/Fhir
   rm -rf app/Http/Middleware/Fhir*
   ```

2. **Rollback de base de datos**:
   ```bash
   php artisan migrate:rollback --step=5  # Revertir últimas migraciones
   ```

3. **Rollback de rutas**:
   - Eliminar líneas de `routes/api.php` que referencian FHIR
   - Restaurar backup de `routes/api.php`

4. **Plan de contingencia**:
   - Mantener APIs existentes funcionales durante implementación
   - Implementar bajo feature flag: `config('services.fhir.enabled')`
   - Si hay fallo, deshabilitar feature flag en `.env`: `FHIR_ENABLED=false`

## Dependencies

- ✅ **Laravel 10+** (ya instalado en proyecto)
- ✅ **PHP 8.2+** (ya instalado)
- ✅ **PostgreSQL 14+** (requerido para JSONB + índices GIN)
- ⚠️ **Laravel Sanctum** (para autenticación de APIs) - `composer require laravel/sanctum`
- ⚠️ **Spatie Activity Log** (para auditoría) - `composer require spatie/laravel-activitylog`
- ⚠️ **HL7 FHIR Library** (para validación) - `composer require hl7/fhir` o similar
- ✅ **Modelos Eloquent existentes** (Patient, Encounter, Observation, etc.)

## Success Criteria

- [ ] **Endpoints funcionales**: 40+ endpoints FHIR respondiendo correctamente (200/201/400/401/403)
- [ ] **Transformación correcta**: 100% de transformers pasan tests unitarios de ida y vuelta (Laravel → FHIR → Laravel)
- [ ] **Validación FHIR**: Recursos generados validan contra schema oficial FHIR R4 (0 errores de validación)
- [ ] **Autenticación funcional**: Endpoints rechazan requests sin token (401) y con permisos insuficientes (403)
- [ ] **Auditoría completa**: 100% de requests FHIR quedan registrados en activity_log con usuario, IP, duración
- [ ] **RDA funcional**: Bundle RDA genera resumen completo con Patient + Encounter + recursos clínicos asociados
- [ ] **Performance**: Búsquedas < 500ms para datasets de hasta 10,000 pacientes
- [ ] **Cobertura de tests**: >80% coverage en controladores y servicios FHIR
- [ ] **Documentación**: Postman collection con todos los endpoints y ejemplos de request/response
