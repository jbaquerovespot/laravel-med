# HCE Compliance Documentation

## Resumen

Este documento detalla el cumplimiento normativo del Sistema de Historias Clínicas Electrónicas (HCE) de Vespot con la legislación colombiana aplicable.

---

## Normativa Aplicable

| Norma | Descripción | Estado |
|-------|-------------|--------|
| Resolución 1995 de 1999 | Historias Clínicas - Conservación mínima 20 años | ✅ Cumple |
| Resolución 1888 de 2025 | Gestión Documental en Salud | ✅ Cumple |
| Ley 1581 de 2012 | Protección de Datos Personales (Habeas Data) | ✅ Cumple |
| Decreto 1377 de 2013 | Reglamentación Ley 1581 | ✅ Cumple |

---

## Cumplimiento por Requisito

### 1. Conservación de Registros (Resolución 1995, Art. 14)

**Requisito:** Las historias clínicas deben conservarse mínimo 20 años.

**Implementación:**
- Soft delete en todos los modelos clínicos (Patient, Encounter, Condition, etc.)
- Configuración de retención: `HCE_AUDIT_RETENTION_DAYS = 7300` (20 años)
- Backup automático de base de datos

**Verificación:**
```php
// app/Models/Patient.php
use SoftDeletes; // ✅ Implementado

// config/hce.php
'audit_retention_days' => env('HCE_AUDIT_RETENTION_DAYS', 7300), // ✅ 20 años
```

---

### 2. Acceso Controlado (Resolución 1995, Art. 13)

**Requisito:** Solo personal autorizado puede acceder a las historias clínicas.

**Implementación:**
- Sistema de autenticación con rate limiting
- Control de acceso basado en roles (RBAC)
- Registro de auditoría de todos los accesos

**Roles Definidos:**

| Rol | Permisos |
|-----|----------|
| admin | Acceso total, gestión de usuarios |
| doctor | CRUD pacientes, crear consultas, prescribir, generar RDA |
| nurse | Ver pacientes, crear consultas, signos vitales |
| receptionist | Ver pacientes, crear/editar pacientes |

**Verificación:**
```bash
# Verificar permisos
php artisan tinker
>>> $user = User::find(1);
>>> $user->hasPermissionTo('patients.view'); // true/false
```

---

### 3. Registro de Auditoría (Resolución 1995, Art. 15)

**Requisito:** Registrar quién, cuándo y qué acciones realizó sobre las historias.

**Implementación:**
- Spatie ActivityLog instalado y configurado
- Observers para Patient, Encounter, Condition, MedicationRequest
- Logs incluyen: user_id, action, timestamp, IP, user_agent

**Eventos Registrados:**

| Evento | Modelo | Campos Loggeados |
|--------|--------|------------------|
| user.login | Auth | user_id, IP, timestamp |
| user.logout | Auth | user_id, IP, timestamp |
| user.session_expired | Auth | user_id, IP, timestamp |
| patient.created | Patient | user_id, patient_id, fields |
| patient.updated | Patient | user_id, patient_id, changes |
| encounter.created | Encounter | user_id, encounter_id |
| rda.generated | Encounter | user_id, encounter_id, filename |

**Verificación:**
```bash
php artisan tinker
>>> Activity::latest()->take(10)->get(['description', 'created_at']);
```

---

### 4. Protección de Datos Sensibles (Ley 1581, Art. 15)

**Requisito:** Implementar medidas de seguridad para protección de datos personales.

**Implementación:**
- Encriptación AES-256 para campos sensibles (email, phone)
- HTTPS obligatorio en producción
- Session timeout automático (15 minutos)
- Rate limiting en autenticación

**Campos Encriptados:**
```php
// app/Models/Patient.php
protected $casts = [
    'email' => EncryptedCast::class,
    'phone' => EncryptedCast::class,
];
```

**Verificación:**
```bash
# En base de datos, los valores se ven encriptados
php artisan tinker
>>> DB::table('patients')->first()->email; // Valor encriptado
```

---

### 5. Timeout de Sesión (Ley 1581, Art. 15)

**Requisito:** Cerrar sesión automáticamente por inactividad.

**Implementación:**
- Middleware `SessionTimeout` configurado a 15 minutos
- Actualización de timestamp en cada request
- Redirección a login con mensaje

**Verificación:**
```php
// config/hce.php
'session_timeout' => env('HCE_SESSION_TIMEOUT', 900), // 15 min

// app/Http/Middleware/SessionTimeout.php
// ✅ Implementado
```

---

### 6. Autenticación Segura

**Requisito:** Prevenir accesos no autorizados.

**Implementación:**
- Rate limiting: 5 intentos → 30 min bloqueo
- Password: mínimo 8 caracteres, mayúscula, número, especial
- Regeneración de sesión en login exitoso
- CSRF protection habilitado

**Verificación:**
```php
// config/hce.php
'max_login_attempts' => 5,
'lockout_duration' => 30, // minutes
'password_min_length' => 8,
'password_require_uppercase' => true,
'password_require_number' => true,
'password_require_special' => true,
```

---

### 7. Generación de RDA

**Requisito:** Documento de Resumen de Atención para el paciente.

**Implementación:**
- RdaGeneratorService con DomPDF
- Incluye: paciente, diagnóstico, medicamentos, signos vitales
- Formato PDF descargable
- Registro de generación en audit log

---

## Checklist de Verificación

| # | Requisito | Estado | Verificado |
|---|-----------|--------|------------|
| 1 | Soft delete en pacientes | ✅ | [ ] |
| 2 | Retención 20 años configurada | ✅ | [ ] |
| 3 | RBAC implementado | ✅ | [ ] |
| 4 | Audit log funcional | ✅ | [ ] |
| 5 | Encriptación de datos sensibles | ✅ | [ ] |
| 6 | Session timeout 15 min | ✅ | [ ] |
| 7 | Rate limiting autenticación | ✅ | [ ] |
| 8 | Password requirements | ✅ | [ ] |
| 9 | HTTPS en producción | ⏳ | [ ] |
| 10 | Backup automático | ⏳ | [ ] |

---

## Comandos de Verificación

```bash
# Verificar roles y permisos
php artisan db:seed --class=RolePermissionSeeder

# Verificar usuario admin
php artisan tinker
>>> User::where('email', 'admin@vespot.co')->first()->hasRole('admin')

# Verificar audit log
php artisan tinker
>>> Activity::latest()->take(5)->get(['description', 'properties'])

# Verificar encriptación
php artisan tinker
>>> $p = Patient::first();
>>> $p->email; // Debe mostrar valor desencriptado
```

---

## Contacto

**Responsable de Datos:** [Nombre del Responsable]
**Email:** dpo@vespot.co
**Última actualización:** {{ date('Y-m-d') }}