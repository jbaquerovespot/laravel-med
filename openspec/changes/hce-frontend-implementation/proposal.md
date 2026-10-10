# Proposal: Sistema de Historias Clínicas Electrónicas (HCE) - Frontend y Flujo Clínico

## Intent

Implementar el sistema completo de Historias Clínicas Electrónicas (HCE) para Vespot SAS, incluyendo la interfaz de usuario con Livewire 3, autenticación segura, gestión de pacientes, flujo clínico completo, soporte biométrico y cumplimiento normativo colombiano (Resolución 1995 de 1999, Resolución 1888 de 2025, Ley 1581 de 2012).

El backend FHIR R4 ya está implementado. Este cambio agrega la capa de presentación, workflows clínicos y características de cumplimiento.

## Scope

### In Scope
- ✅ **Autenticación segura** con login/password (session timeout, intentos fallidos, 2FA opcional)
- ✅ **Componentes Livewire 3** para gestión de pacientes, encuentros, observaciones, diagnósticos, medicamentos
- ✅ **Validación de documentos colombianos** (cédula, TI, pasaporte) como ID único
- ✅ **Búsqueda de pacientes** por cédula, nombre, teléfono con Livewire
- ✅ **Flujo clínico completo**: motivo consulta → anamnesis → examen físico → diagnóstico → tratamiento
- ✅ **Receta electrónica** con validación de alergias e interacciones medicamentosas
- ✅ **Solicitud de exámenes** con resultados y valores de referencia
- ✅ **Signos vitales** con alertas de valores anormales
- ✅ **Codificación diagnóstica** CIE-10/SNOMED CT
- ✅ **Generación RDA** (Resumen Digital de Atención) en PDF
- ✅ **Dashboard médico** en tiempo real
- ✅ **Auditoría completa** de accesos y modificaciones
- ✅ **Encriptación** de datos sensibles (Ley 1581)
- ⚠️ **Soporte biométrico** (huella digital) - Fase 2

### Out of Scope
- ❌ **Integración con laboratorios externos** - Futuro
- ❌ **FHIR Subscriptions/Messaging** - Solo REST API
- ❌ **Notificaciones push en tiempo real** - Futuro
- ❌ **App móvil** - Solo web responsive

## Approach

Implementación en fases sobre la arquitectura existente:

1. **Fase 1 - Autenticación y Seguridad** (Sem 1-2)
   - Login con rate limiting y session timeout
   - Roles y permisos (Spatie Permission)
   - Audit logging automático

2. **Fase 2 - Gestión de Pacientes** (Sem 3-4)
   - Componentes Livewire para CRUD
   - Validación de documentos colombianos
   - Búsqueda reactiva

3. **Fase 3 - Flujo Clínico** (Sem 5-8)
   - Encuentros médicos completos
   - Observaciones y signos vitales
   - Diagnósticos CIE-10
   - Recetas electrónicas

4. **Fase 4 - Cumplimiento** (Sem 9-10)
   - Encriptación de datos sensibles
   - RDA Generator con PDF
   - Dashboard médico

5. **Fase 5 - Biometría** (Sem 11-12) - Opcional
   - Integración con lectores de huella
   - Autenticación biométrica

## Affected Areas

| Area | Impact | Descripción |
|------|--------|-------------|
| `app/Livewire/` | Nuevo | Componentes Livewire para Auth, Patients, Encounters, Observations, Conditions, Medications |
| `app/Http/Controllers/Auth/` | Modificado | Login con rate limiting, 2FA, session management |
| `app/Rules/ValidColombianDocument.php` | Nuevo | Validación de documentos colombianos |
| `app/Services/BiometricService.php` | Nuevo | Servicio de integración biométrica |
| `app/Casts/EncryptedCast.php` | Nuevo | Cast para encriptación de campos sensibles |
| `app/Observers/` | Nuevo | Observers para audit logging automático |
| `resources/views/livewire/` | Nuevo | Vistas Blade para componentes Livewire |
| `resources/views/components/` | Nuevo | Componentes Blade reutilizables |
| `routes/web.php` | Modificado | Rutas para UI web |
| `config/permission.php` | Nuevo | Configuración de roles y permisos |
| `database/seeders/` | Modificado | Seed de roles, permisos, usuario admin |
| `tests/Feature/Livewire/` | Nuevo | Tests de integración para componentes |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| **Complejidad de validación de documentos** | Medium | Usar regex probadas; tests exhaustivos con documentos válidos/inválidos |
| **Rendimiento con muchos pacientes** | Medium | Índices en document_number, name; paginación; eager loading |
| **Errores en flujo clínico** | High | UX testing con médicos reales; validación paso a paso; mensajes claros |
| **Cumplimiento normativo incompleto** | Medium | Checklist contra Resolución 1995/1888; revisión legal |
| **Integración biométrica problemática** | High | Fase opcional; testear con SDK del fabricante; fallback a login tradicional |
| **Interacciones medicamentosas no detectadas** | Medium | Base de datos de interacciones; alertas pero no bloqueo automático |

## Rollback Plan

1. **Rollback de código**:
   ```bash
   git revert <commit-hce-frontend>
   # O eliminar directorios específicos:
   rm -rf app/Livewire
   rm -rf resources/views/livewire
   ```

2. **Rollback de base de datos**:
   ```bash
   php artisan migrate:rollback --step=3
   ```

3. **Feature flags**:
   ```env
   HCE_BIOMETRIC_ENABLED=false
   HCE_ENCRYPTION_ENABLED=false
   ```

## Dependencies

- ✅ **Laravel 11** (instalado)
- ✅ **PHP 8.2+** (instalado)
- ✅ **Livewire 3** - `composer require livewire/livewire`
- ✅ **Spatie Permission** - `composer require spatie/laravel-permission`
- ✅ **Spatie Activity Log** (instalado)
- ✅ **TailwindCSS** (instalado)
- ⚠️ **DomPDF** para RDA - `composer require barryvdh/laravel-dompdf`
- ⚠️ **Laravel UI** para auth scaffolding (opcional)
- ⚠️ **SDK Biométrico** (DigitalPersona/Suprema) - solo si se implementa Fase 5

## Success Criteria

- [ ] **Login funcional**: Autenticación con rate limiting (5 intentos), session timeout (15 min)
- [ ] **CRUD Pacientes**: Crear, editar, buscar pacientes con validación de cédula
- [ ] **Flujo clínico completo**: Motivo consulta → diagnóstico → tratamiento en un encuentro
- [ ] **Recetas electrónicas**: Generar PDF de receta con validación de alergias
- [ ] **Signos vitales**: Registrar con alertas de valores anormales
- [ ] **RDA**: Generar PDF del Resumen Digital de Atención
- [ ] **Auditoría**: 100% de accesos registrados en activity_log
- [ ] **Encriptación**: Datos sensibles (email, phone) encriptados en BD
- [ ] **Performance**: Búsqueda de pacientes < 300ms
- [ ] **Tests**: >70% coverage en componentes Livewire
- [ ] **Cumplimiento**: Checklist de Resolución 1995/1888 completado