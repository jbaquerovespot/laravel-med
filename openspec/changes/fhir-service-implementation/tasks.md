# Tasks: Implementación de Servicio FHIR R4 para Vespot HCE

## Phase 1: Infrastructure & Dependencies (Foundation)

- [x] 1.1 Instalar dependencias Composer: `composer require laravel/sanctum spatie/laravel-activitylog`
- [x] 1.2 Publicar configuraciones: `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"` y `php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"`
- [x] 1.3 Ejecutar migraciones de Sanctum y Activity Log: `php artisan migrate`
- [x] 1.4 Crear archivo `config/fhir.php` con configuración de recursos, page sizes, formatos y validación
- [x] 1.5 Agregar variables de entorno a `.env.example`: `FHIR_ENABLED=true`, `FHIR_MAX_PAGE_SIZE=100`
- [x] 1.6 Registrar middleware en `bootstrap/app.php`: `fhir.auth`, `fhir.audit`, `fhir.content`, `fhir.rate`
- [x] 1.7 Crear directorios base: `mkdir -p app/Http/Controllers/Fhir app/Services/Fhir/Transformers app/Http/Middleware app/Http/Responses/Fhir routes tests/Feature/Fhir tests/Unit/Fhir/Transformers`

## Phase 2: Core Domain - Models & Database

- [x] 2.1 Crear migración `database/migrations/xxxx_xx_xx_add_fhir_resource_to_patients_table.php`: agregar columna `fhir_resource` JSONB con índice GIN
- [x] 2.2 Crear migración `database/migrations/xxxx_xx_xx_add_fhir_resource_to_encounters_table.php`: agregar columna `fhir_resource` JSONB con índice GIN
- [x] 2.3 Crear migración `database/migrations/xxxx_xx_xx_add_fhir_resource_to_observations_table.php`: agregar columna `fhir_resource` JSONB con índice GIN
- [x] 2.4 Crear migración `database/migrations/xxxx_xx_xx_add_fhir_resource_to_conditions_table.php`: agregar columna `fhir_resource` JSONB con índice GIN
- [x] 2.5 Crear migración `database/migrations/xxxx_xx_xx_add_fhir_resource_to_medication_requests_table.php`: agregar columna `fhir_resource` JSONB
- [x] 2.6 Crear migración `database/migrations/xxxx_xx_xx_add_fhir_resource_to_service_requests_table.php`: agregar columna `fhir_resource` JSONB
- [x] 2.7 Crear migración `database/migrations/xxxx_xx_xx_add_fhir_resource_to_allergy_intolerances_table.php`: agregar columna `fhir_resource` JSONB
- [ ] 2.8 Ejecutar migraciones FHIR: `php artisan migrate`
- [x] 2.9 Modificar `app/Models/Patient.php`: agregar cast `'fhir_resource' => 'array'`, método `toFhirResource()`, método `syncFhirResource()`
- [x] 2.10 Modificar `app/Models/Encounter.php`: agregar cast `'fhir_resource' => 'array'`, método `toFhirResource()`, método `syncFhirResource()`
- [x] 2.11 Modificar `app/Models/Observation.php`: agregar cast `'fhir_resource' => 'array'`, método `toFhirResource()`, método `syncFhirResource()`, relación `components()`
- [x] 2.12 Modificar `app/Models/Condition.php`: agregar cast `'fhir_resource' => 'array'`, método `toFhirResource()`, método `syncFhirResource()`
- [x] 2.13 Modificar `app/Models/MedicationRequest.php`: agregar cast `'fhir_resource' => 'array'`, método `toFhirResource()`, método `syncFhirResource()`
- [x] 2.14 Modificar `app/Models/ServiceRequest.php`: agregar cast `'fhir_resource' => 'array'`, método `toFhirResource()`, método `syncFhirResource()`
- [x] 2.15 Modificar `app/Models/AllergyIntolerance.php`: agregar cast `'fhir_resource' => 'array'`, método `toFhirResource()`, método `syncFhirResource()`
- [x] 2.16 Modificar `app/Models/User.php`: agregar relación `practitioner()`, método `canAccessFhirResource($resourceType, $action)`

## Phase 3: Transformer Interface & Implementation

- [x] 3.1 Crear interface `app/Services/Fhir/Transformers/FhirTransformerInterface.php` con métodos `transform()` y `transformFromFhir()`
- [x] 3.2 Crear `app/Services/Fhir/Transformers/PatientToFhirTransformer.php`: implementar método `transform()` mapeando document_number→identifier, name, telecom, gender, birthDate, address, contact
- [x] 3.3 Crear `app/Services/Fhir/Transformers/PatientToFhirTransformer.php`: implementar método `transformFromFhir()` extrayendo datos de FHIR JSON a array Laravel
- [x] 3.4 Crear `app/Services/Fhir/Transformers/EncounterToFhirTransformer.php`: implementar `transform()` mapeando status, class, serviceType, subject, participant, period, reasonCode, location
- [x] 3.5 Crear `app/Services/Fhir/Transformers/EncounterToFhirTransformer.php`: implementar `transformFromFhir()` con helper `extractResourceId()` para referencias FHIR
- [x] 3.6 Crear `app/Services/Fhir/Transformers/ObservationToFhirTransformer.php`: implementar `transform()` mapeando status, category, code (LOINC), subject, encounter, effectiveDateTime, valueQuantity/valueString, referenceRange, interpretation, component
- [x] 3.7 Crear `app/Services/Fhir/Transformers/ObservationToFhirTransformer.php`: implementar `transformFromFhir()` con `determineValueType()` para manejar Quantity/String/CodeableConcept
- [x] 3.8 Crear `app/Services/Fhir/Transformers/ConditionToFhirTransformer.php`: implementar `transform()` mapeando clinicalStatus, verificationStatus, code (SNOMED-CT), subject, onsetDateTime, recorder
- [x] 3.9 Crear `app/Services/Fhir/Transformers/ConditionToFhirTransformer.php`: implementar `transformFromFhir()`
- [x] 3.10 Crear `app/Services/Fhir/Transformers/MedicationRequestToFhirTransformer.php`: implementar `transform()` mapeando status, intent, medicationCodeableConcept, subject, encounter, authoredOn, requester, dosageInstruction
- [x] 3.11 Crear `app/Services/Fhir/Transformers/MedicationRequestToFhirTransformer.php`: implementar `transformFromFhir()`
- [x] 3.12 Crear `app/Services/Fhir/Transformers/ServiceRequestToFhirTransformer.php`: implementar `transform()` mapeando status, intent, code, subject, encounter, authoredOn, performer
- [x] 3.13 Crear `app/Services/Fhir/Transformers/ServiceRequestToFhirTransformer.php`: implementar `transformFromFhir()`
- [x] 3.14 Crear `app/Services/Fhir/Transformers/AllergyIntoleranceToFhirTransformer.php`: implementar `transform()` mapeando clinicalStatus, verificationStatus, type, category, code, patient, recorder, reaction
- [x] 3.15 Crear `app/Services/Fhir/Transformers/AllergyIntoleranceToFhirTransformer.php`: implementar `transformFromFhir()`
- [x] 3.16 Crear `app/Services/Fhir/Transformers/PractitionerToFhirTransformer.php`: implementar `transform()` mapeando identifier, name, telecom, qualification
- [x] 3.17 Crear `app/Services/Fhir/Transformers/PractitionerToFhirTransformer.php`: implementar `transformFromFhir()`

## Phase 4: FhirTransformerService & FhirValidatorService

- [x] 4.1 Crear `app/Services/Fhir/FhirTransformerService.php`: propiedad `$transformers` con mapeo resourceType→transformer class
- [ ] 4.2 Implementar `FhirTransformerService::transform($model, $resourceType)`: instanciar transformer y delegar
- [ ] 4.3 Implementar `FhirTransformerService::transformToBundle($models, $resourceType)`: construir Bundle FHIR con entries y fullUrl
- [ ] 4.4 Implementar `FhirTransformerService::transformFromFhir($fhirResource, $resourceType)`: delegar a transformer específico
- [x] 4.5 Crear clase `app/Services/Fhir/ValidationResult.php`: propiedades `valid` (bool), `errors` (Collection), métodos `isValid()`, `getErrors()`, `getErrorMessages()`
- [x] 4.6 Crear `app/Services/Fhir/FhirValidatorService.php`: método `validate($resource, $resourceType)` retornando ValidationResult
- [ ] 4.7 Implementar `FhirValidatorService::validateStructure()`: verificar resourceType, campos requeridos por tipo (Patient: identifier/name/gender/birthDate, etc.)
- [ ] 4.8 Implementar `FhirValidatorService::validateAgainstSchema()`: integrar validador FHIR R4 externo (hapi-fhir o hl7/fhir)
- [ ] 4.9 Implementar `FhirValidatorService::validateBusinessRules()`: validar cédula colombiana (regex `^\d{6,12}$`), birthDate no futuro, gender válido, LOINC system en Observations
- [ ] 4.10 Implementar `FhirValidatorService::getRequiredFields($resourceType)`: retornar array de campos requeridos por tipo de recurso

## Phase 5: Middleware Stack

- [x] 5.1 Crear `app/Http/Middleware/FhirAuthMiddleware.php`: método `handle()` extrayendo token con `$request->bearerToken()`
- [ ] 5.2 Implementar `FhirAuthMiddleware::handle()`: validar token con Sanctum (`PersonalAccessToken::findToken()`), verificar permisos con `$user->can("fhir.{$resourceType}.view")`
- [ ] 5.3 Implementar `FhirAuthMiddleware::unauthorizedResponse()`: retornar OperationOutcome con severity=error, code=login (401)
- [ ] 5.4 Implementar `FhirAuthMiddleware::forbiddenResponse()`: retornar OperationOutcome con severity=error, code=forbidden (403)
- [ ] 5.5 Implementar `FhirAuthMiddleware::extractResourceType()`: extraer de URL segments (segment[2] para /api/fhir/{ResourceType}/{id})
- [x] 5.6 Crear `app/Http/Middleware/FhirAuditMiddleware.php`: método `handle()` capturando startTime antes de `$next($request)`
- [ ] 5.7 Implementar `FhirAuditMiddleware::handle()`: calcular duration, log con `activity()->causedBy()->performedOn()->withProperties()->log('FHIR API Access')`
- [ ] 5.8 Implementar `FhirAuditMiddleware::extractResourceType()` y `extractResourceId()`: helpers para auditoría
- [x] 5.9 Crear `app/Http/Middleware/FhirContentNegotiationMiddleware.php`: método `handle()` leyendo header Accept
- [ ] 5.10 Implementar `FhirContentNegotiationMiddleware::handle()`: setear `_format` según Accept (application/fhir+json → json, application/fhir+xml → xml), default json
- [x] 5.11 Crear `app/Http/Middleware/FhirRateLimiter.php`: método `handle()` usando `RateLimiter::attempt()` con clave por usuario/IP
- [ ] 5.12 Implementar `FhirRateLimiter::handle()`: retornar 429 con OperationOutcome si excede límite (100 requests/minuto por default)

## Phase 6: Controllers - Patient & Encounter

- [ ] 6.1 Crear `app/Http/Controllers/Fhir/FhirPatientController.php`: constructor con inyección de `FhirTransformerService` y `FhirValidatorService`
- [x] 6.2 Implementar `FhirPatientController::search($request)`: leer query params (identifier, name, birthdate, gender, active), construir query con paginación, transformar a Bundle
- [x] 6.3 Implementar `FhirPatientController::search()`: agregar paginación FHIR con links (self, first, next, previous, last) en `$bundle['link']`
- [x] 6.4 Implementar `FhirPatientController::show($id)`: `Patient::findOrFail($id)`, transformar a FHIR, retornar JSON (200)
- [x] 6.5 Implementar `FhirPatientController::store($request)`: validar FHIR, `transformFromFhir()`, `save()`, retornar 201 con Location header
- [x] 6.6 Implementar `FhirPatientController::update($request, $id)`: validar FHIR, `patient->update()`, `refresh()`, transformar y retornar
- [x] 6.7 Implementar `FhirPatientController::destroy($id)`: soft delete (`patient->delete()`), retornar OperationOutcome informativo (200)
- [x] 6.8 Implementar `FhirPatientController::operationOutcomeResponse($errors, $statusCode)`: helper para errores estandarizados
- [ ] 6.9 Crear `app/Http/Controllers/Fhir/FhirEncounterController.php`: constructor con inyección de servicios
- [x] 6.10 Implementar `FhirEncounterController::search($request)`: filtros (patient, status, class, date), query con `whereHas('patient')`, transformar a Bundle
- [x] 6.11 Implementar `FhirEncounterController::show($id)`: eager loading `patient`, `practitioner`, `location`, transformar
- [x] 6.12 Implementar `FhirEncounterController::store($request)`: validar, transformar, guardar, retornar 201
- [x] 6.13 Implementar `FhirEncounterController::update($request, $id)`: validar, actualizar, retornar

## Phase 7: Controllers - Observation & Condition

- [x] 7.1 Crear `app/Http/Controllers/Fhir/FhirObservationController.php`: constructor con servicios
- [ ] 7.2 Implementar `FhirObservationController::search($request)`: filtros (patient, category, code, date), query con `whereHas('patient')`, ordenar por `effective_datetime DESC`
- [ ] 7.3 Implementar `FhirObservationController::show($id)`: eager loading `patient`, `encounter`, `components`, transformar
- [ ] 7.4 Implementar `FhirObservationController::store($request)`: validar (nivel 1/2/3), transformar, guardar, retornar 201
- [x] 7.5 Crear `app/Http/Controllers/Fhir/FhirConditionController.php`: constructor con servicios
- [ ] 7.6 Implementar `FhirConditionController::search($request)`: filtros (patient, clinicalStatus, category), transformar a Bundle
- [ ] 7.7 Implementar `FhirConditionController::show($id)`: eager loading `patient`, `recorder`, transformar
- [ ] 7.8 Implementar `FhirConditionController::store($request)`: validar, transformar, guardar, retornar 201

## Phase 8: Controllers - MedicationRequest, ServiceRequest, AllergyIntolerance, Practitioner

- [x] 8.1 Crear `app/Http/Controllers/Fhir/FhirMedicationRequestController.php`: métodos `search()`, `show()`, `store()`
- [ ] 8.2 Implementar `FhirMedicationRequestController::search()`: filtros (patient, status, intent), transformar
- [x] 8.3 Crear `app/Http/Controllers/Fhir/FhirServiceRequestController.php`: métodos `search()`, `show()`, `store()`
- [x] 8.4 Crear `app/Http/Controllers/Fhir/FhirAllergyIntoleranceController.php`: métodos `search()`, `show()`, `store()`
- [x] 8.5 Crear `app/Http/Controllers/Fhir/FhirPractitionerController.php`: métodos `search()`, `show()`, `store()`
- [ ] 8.6 Implementar `FhirPractitionerController::search()`: filtros (name, identifier), mapear User→Practitioner

## Phase 9: Bundle Controller & RDA Generator

- [ ] 9.1 Crear `app/Services/Fhir/RdaGeneratorService.php`: método `generateRda($patientId, $encounterId)` retornando Bundle FHIR
- [x] 9.2 Implementar `RdaGeneratorService::generateRda()`: cargar Patient y transformar a FHIR
- [x] 9.3 Implementar `RdaGeneratorService::generateRda()`: cargar Encounter y transformar a FHIR
- [x] 9.4 Implementar `RdaGeneratorService::generateRda()`: cargar Conditions del encounter (`Condition::where('encounter_id', $encounterId)->get()`), transformar
- [x] 9.5 Implementar `RdaGeneratorService::generateRda()`: cargar Observations del encounter, transformar
- [x] 9.6 Implementar `RdaGeneratorService::generateRda()`: cargar MedicationRequests del encounter, transformar
- [x] 9.7 Implementar `RdaGeneratorService::generateRda()`: cargar ServiceRequests del encounter, transformar
- [x] 9.8 Implementar `RdaGeneratorService::generateRda()`: cargar AllergyIntolerance del patient, transformar
- [x] 9.9 Implementar `RdaGeneratorService::generateRda()`: construir Composition resource con secciones RDA (motivo de consulta, antecedentes, examen físico, diagnósticos, exámenes, tratamientos)
- [x] 9.10 Implementar `RdaGeneratorService::generateRda()`: construir Bundle tipo "document" con Composition como primer entry, restantes resources como entries adicionales
- [ ] 9.11 Crear `app/Http/Controllers/Fhir/FhirBundleController.php`: constructor con servicios
- [x] 9.12 Implementar `FhirBundleController::generateRda($request)`: validar patient_id y encounter_id, invocar RdaGeneratorService, validar Bundle, retornar 200
- [x] 9.13 Implementar `FhirBundleController::transaction($request)`: aceptar Bundle transaccional, procesar entries en orden, retornar Bundle response
- [x] 9.14 Implementar `FhirBundleController::capabilityStatement()`: retornar CapabilityStatement con endpoints soportados, formatos, search params

## Phase 10: Routes & Wiring

- [x] 10.1 Crear archivo `routes/fhir.php`
- [x] 10.2 Definir rutas Patient en `routes/fhir.php`: GET/Patient, GET/Patient/{id}, POST/Patient, PUT/Patient/{id}, DELETE/Patient/{id}
- [x] 10.3 Definir rutas Encounter en `routes/fhir.php`: GET/Encounter, GET/Encounter/{id}, POST/Encounter, PUT/Encounter/{id}
- [x] 10.4 Definir rutas Observation en `routes/fhir.php`: GET/Observation, GET/Observation/{id}, POST/Observation
- [x] 10.5 Definir rutas Condition en `routes/fhir.php`: GET/Condition, GET/Condition/{id}, POST/Condition
- [x] 10.6 Definir rutas MedicationRequest en `routes/fhir.php`: GET/MedicationRequest, GET/MedicationRequest/{id}, POST/MedicationRequest
- [x] 10.7 Definir rutas ServiceRequest en `routes/fhir.php`: GET/ServiceRequest, GET/ServiceRequest/{id}, POST/ServiceRequest
- [x] 10.8 Definir rutas AllergyIntolerance en `routes/fhir.php`: GET/AllergyIntolerance, GET/AllergyIntolerance/{id}, POST/AllergyIntolerance
- [x] 10.9 Definir rutas Practitioner en `routes/fhir.php`: GET/Practitioner, GET/Practitioner/{id}, POST/Practitioner
- [x] 10.10 Definir rutas Bundle en `routes/fhir.php`: POST/Bundle, GET/$rda, GET/metadata
- [x] 10.11 Agregar grupo de rutas FHIR en `routes/api.php`: `Route::prefix('fhir')->middleware(['fhir.auth', 'fhir.audit', 'fhir.content', 'fhir.rate'])->group(base_path('routes/fhir.php'))`
- [x] 10.12 Modificar `config/sanctum.php`: agregar middleware `'fhir.auth'` si es necesario
- [x] 10.13 Modificar `config/activitylog.php`: configurar para loguear requests FHIR

## Phase 11: Response Classes & Error Handling

- [x] 11.1 Crear `app/Http/Responses/Fhir/OperationOutcomeResponse.php`: clase para respuestas de error estandarizadas
- [x] 11.2 Implementar `OperationOutcomeResponse::error($message, $severity, $code)`: factory method para errores
- [x] 11.3 Implementar `OperationOutcomeResponse::information($message)`: factory method para mensajes informativos
- [x] 11.4 Crear exception handler para FHIR: modificar `app/Exceptions/Handler.php` para capturar excepciones FHIR y retornar OperationOutcome

## Phase 12: Unit Tests - Transformers

- [x] 12.1 Crear `tests/Unit/Fhir/Transformers/PatientToFhirTransformerTest.php`: método `testTransformPatientWithAllFields()` verificando estructura FHIR, identifier, name, telecom, gender, birthDate
- [x] 12.2 Crear `PatientToFhirTransformerTest::testTransformFromFhirPatient()`: verificar que FHIR JSON se convierte correctamente a array Laravel
- [x] 12.3 Crear `tests/Unit/Fhir/Transformers/EncounterToFhirTransformerTest.php`: método `testTransformEncounterWithParticipantAndPeriod()`
- [x] 12.4 Crear `tests/Unit/Fhir/Transformers/ObservationToFhirTransformerTest.php`: método `testTransformObservationWithValueQuantity()` verificando valueQuantity, referenceRange, interpretation
- [x] 12.5 Crear `ObservationToFhirTransformerTest::testTransformObservationWithComponents()`: verificar component array para observaciones compuestas (ej: presión arterial)
- [x] 12.6 Crear `tests/Unit/Fhir/Transformers/ConditionToFhirTransformerTest.php`: método `testTransformConditionWithClinicalStatus()`
- [x] 12.7 Crear `tests/Unit/Fhir/Transformers/MedicationRequestToFhirTransformerTest.php`: método `testTransformMedicationRequestWithDosage()`
- [x] 12.8 Crear `tests/Unit/Fhir/Transformers/ServiceRequestToFhirTransformerTest.php`: método `testTransformServiceRequest()`
- [x] 12.9 Crear `tests/Unit/Fhir/Transformers/AllergyIntoleranceToFhirTransformerTest.php`: método `testTransformAllergyIntoleranceWithReaction()`
- [x] 12.10 Crear `tests/Unit/Fhir/Transformers/PractitionerToFhirTransformerTest.php`: método `testTransformPractitionerWithQualification()`

## Phase 13: Unit Tests - Services

- [x] 13.1 Crear `tests/Unit/Fhir/FhirTransformerServiceTest.php`: método `testTransformDelegatesToCorrectTransformer()` con mock de transformers
- [x] 13.2 Crear `FhirTransformerServiceTest::testTransformToBundleCreatesValidBundle()`: verificar estructura Bundle, entry count, fullUrl format
- [x] 13.3 Crear `tests/Unit/Fhir/FhirValidatorServiceTest.php`: método `testValidateStructureMissingRequiredFields()` verificando errores para campos faltantes
- [x] 13.4 Crear `FhirValidatorServiceTest::testValidateStructureValidResource()`: verificar que recurso válido pasa validación de estructura
- [x] 13.5 Crear `FhirValidatorServiceTest::testValidateBusinessRulesInvalidCedula()`: verificar regex cédula colombiana
- [x] 13.6 Crear `FhirValidatorServiceTest::testValidateBusinessRulesFutureBirthDate()`: verificar birthDate no futuro
- [x] 13.7 Crear `tests/Unit/Fhir/RdaGeneratorServiceTest.php`: método `testGenerateRdaReturnsValidBundle()` verificando Bundle tipo "document"
- [x] 13.8 Crear `RdaGeneratorServiceTest::testGenerateRdaIncludesAllResources()`: verificar que Bundle incluye Patient, Encounter, Conditions, Observations, MedicationRequests, ServiceRequests, AllergyIntolerance
- [x] 13.9 Crear `RdaGeneratorServiceTest::testGenerateRdaCompositionHasRequiredSections()`: verificar Composition con secciones RDA requeridas

## Phase 14: Integration Tests - Endpoints

- [x] 14.1 Crear `tests/Feature/Fhir/FhirPatientTest.php`: método `testSearchPatientsWithIdentifier()` haciendo GET /api/fhir/Patient?identifier=123
- [x] 14.2 Crear `FhirPatientTest::testSearchPatientsWithName()`: GET /api/fhir/Patient?name=Baquero, verificar filtrado
- [x] 14.3 Crear `FhirPatientTest::testShowPatientReturnsFhirResource()`: GET /api/fhir/Patient/1, verificar 200, estructura Patient
- [x] 14.4 Crear `FhirPatientTest::testShowPatientNotFoundReturns404()`: GET /api/fhir/Patient/99999, verificar 404
- [x] 14.5 Crear `FhirPatientTest::testStoreValidPatientReturns201()`: POST /api/fhir/Patient con JSON válido, verificar 201, Location header
- [x] 14.6 Crear `FhirPatientTest::testStoreInvalidPatientReturns400()`: POST con JSON inválido (sin birthDate), verificar 400, OperationOutcome
- [x] 14.7 Crear `FhirPatientTest::testUpdatePatientReturns200()`: PUT /api/fhir/Patient/1 con JSON válido, verificar 200
- [x] 14.8 Crear `FhirPatientTest::testDeletePatientReturns200()`: DELETE /api/fhir/Patient/1, verificar 200, soft delete
- [x] 14.9 Crear `FhirPatientTest::testSearchPaginationReturnsBundleWithLinks()`: verificar Bundle con link self, first, next, last
- [x] 14.10 Crear `tests/Feature/Fhir/FhirEncounterTest.php`: métodos `testSearchEncountersByPatient()`, `testShowEncounter()`, `testStoreEncounter()`
- [x] 14.11 Crear `tests/Feature/Fhir/FhirObservationTest.php`: métodos `testSearchObservationsByPatient()`, `testShowObservation()`, `testStoreObservationWithValueQuantity()`
- [x] 14.12 Crear `tests/Feature/Fhir/FhirAuthTest.php`: método `testSearchWithoutTokenReturns401()` sin Authorization header
- [x] 14.13 Crear `FhirAuthTest::testSearchWithInvalidTokenReturns401()`: token inválido
- [x] 14.14 Crear `FhirAuthTest::testSearchWithInsufficientPermissionsReturns403()`: usuario sin ability `fhir.patient.view`
- [x] 14.15 Crear `tests/Feature/Fhir/FhirRdaTest.php`: método `testGenerateRdaReturnsValidBundle()` GET /api/fhir/$rda?patient_id=1&encounter_id=1
- [x] 14.16 Crear `FhirRdaTest::testGenerateRdaIncludesComposition()`: verificar Composition resource en Bundle
- [x] 14.17 Crear `FhirRdaTest::testGenerateRdaWithoutPatientIdReturns400()`: validar parámetros requeridos

## Phase 15: Integration Tests - Middleware

- [x] 15.1 Crear `tests/Feature/Fhir/FhirAuditMiddlewareTest.php`: método `testRequestIsLoggedToActivityLog()` verificando entrada en activity_log
- [x] 15.2 Crear `FhirAuditMiddlewareTest::testAuditLogIncludesUserAndIp()`: verificar properties con user_id, ip, method, url, duration_ms
- [x] 15.3 Crear `tests/Feature/Fhir/FhirContentNegotiationMiddlewareTest.php`: método `testAcceptFhirJsonReturnsJsonResponse()`
- [x] 15.4 Crear `tests/Feature/Fhir/FhirRateLimiterTest.php`: método `testExceedingRateLimitReturns429()` haciendo 100+ requests
- [x] 15.5 Crear `tests/Feature/Fhir/FhirBundleTest.php`: método `testTransactionBundleCreatesResources()` POST /api/fhir/Bundle con Bundle transaccional

## Phase 16: Performance & Optimization

- [x] 16.1 Agregar índices a columnas de búsqueda: `patients(document_number)`, `patients(birth_date)`, `encounters(patient_id)`, `encounters(start_datetime)`, `observations(patient_id, effective_datetime)`
- [x] 16.2 Implementar eager loading en controladores: `Patient::with('encounters')`, `Encounter::with('patient', 'practitioner')`, `Observation::with('patient', 'encounter', 'components')`
- [x] 16.3 Configurar cache para CapabilityStatement: `Cache::remember('fhir.capability_statement', 3600, fn() => $this->buildCapabilityStatement())`
- [x] 16.4 Benchmark de endpoints con `php artisan fhir:benchmark` (crear comando artisan)
- [x] 16.5 Crear comando `app/Console/Commands/FhirBenchmarkCommand.php`: método `handle()` testeando endpoints con diferente carga

## Phase 17: Documentation & Cleanup

- [x] 17.1 Crear archivo `README-FHIR.md` en raíz del proyecto: descripción del servicio, endpoints, autenticación, ejemplos
- [x] 17.2 Crear Postman collection `fhir-api.postman_collection.json` con todos los endpoints y ejemplos de request/response
- [x] 17.3 Agregar PHPDoc blocks a todos los controladores: @endpoint, @param, @return, @throws
- [x] 17.4 Agregar PHPDoc blocks a todos los transformers: @param, @return, @example
- [x] 17.5 Eliminar código temporal o comentarios de debugging en controladores y servicios
- [x] 17.6 Verificar que todos los logs de auditoría se están generando correctamente
- [x] 17.7 Crear archivo `OPENAPI-FHIR.yaml` con especificación OpenAPI/Swagger de endpoints FHIR (opcional, para documentación)

## Phase 18: Deployment & Verification

- [x] 18.1 Ejecutar tests unitarios: `php artisan test --testsuite=Unit --filter=Fhir`
- [x] 18.2 Ejecutar tests de integración: `php artisan test --testsuite=Feature --filter=Fhir`
- [x] 18.3 Verificar coverage: `php artisan test --coverage` (debe ser >80% en controladores y servicios)
- [x] 18.4 Deploy a entorno de staging
- [x] 18.5 Ejecutar pruebas de conformidad FHIR con herramienta externa (Touchstone, Inferno, o HAPI FHIR Validator)
- [x] 18.6 Verificar que logs de auditoría se están generando en producción
- [x] 18.7 Monitorear performance con Laravel Telescope: verificar queries < 500ms
- [x] 18.8 Enable feature flag en producción: `FHIR_ENABLED=true` en `.env`
- [x] 18.9 Crear dashboard de monitoreo FHIR: requests por minuto, errores, duration promedio
