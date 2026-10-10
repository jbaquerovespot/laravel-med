# 🏥 API FHIR - Vespot HCE

API FHIR R4 para el sistema de Historias Clínicas Electrónicas de Vespot SAS.

## 📋 Características

- ✅ **FHIR R4** Compatible con estándar HL7 FHIR Release 4
- ✅ **Resolución 1888 de 2025** Cumplimiento RDA (Resumen Digital de Atención)
- ✅ **8 Recursos FHIR** Patient, Encounter, Observation, Condition, MedicationRequest, ServiceRequest, AllergyIntolerance, Practitioner
- ✅ **Autenticación** Laravel Sanctum con Bearer tokens
- ✅ **Auditoría** Logs completos con Spatie Activity Log
- ✅ **Validación** 3 niveles (estructura, schema, reglas de negocio)

## 🚀 Inicio Rápido

### Prerrequisitos

- PHP 8.2+
- PostgreSQL 14+
- Composer
- Laravel 11

### Instalación

```bash
# Clonar repositorio
git clone <repo-url>
cd laravel-med

# Instalar dependencias
composer install

# Configurar variables de entorno
cp .env.example .env
# Editar .env con credenciales de PostgreSQL

# Generar key
php artisan key:generate

# Ejecutar migraciones
php artisan migrate

# Iniciar servidor
php artisan serve
```

### Configuración PostgreSQL

```env
DB_CONNECTION=pgsql
DB_HOST=devops
DB_PORT=5432
DB_DATABASE=medhis
DB_USERNAME=medhis
DB_PASSWORD=medhis
DB_SCHEMA=medhis
```

## 🔐 Autenticación

### Obtener Token

```bash
php artisan tinker
>>> $user = User::first();
>>> echo $user->createToken('fhir-api')->plainTextToken;
```

### Usar Token

```bash
curl -X GET http://localhost:8000/api/fhir/Patient \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/fhir+json"
```

## 📚 Endpoints FHIR

### Patient

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/fhir/Patient` | Buscar pacientes |
| GET | `/api/fhir/Patient/{id}` | Obtener paciente por ID |
| POST | `/api/fhir/Patient` | Crear paciente |
| PUT | `/api/fhir/Patient/{id}` | Actualizar paciente |
| DELETE | `/api/fhir/Patient/{id}` | Eliminar paciente (soft delete) |

**Parámetros de búsqueda**:
- `identifier` - Número de documento
- `name` - Nombre o apellido
- `birthdate` - Fecha de nacimiento
- `gender` - Género (male, female, other, unknown)
- `active` - Estado activo (true/false)
- `_count` - Paginación (default: 20, max: 100)

### Encounter

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/fhir/Encounter` | Buscar encuentros |
| GET | `/api/fhir/Encounter/{id}` | Obtener encuentro |
| POST | `/api/fhir/Encounter` | Crear encuentro |
| PUT | `/api/fhir/Encounter/{id}` | Actualizar encuentro |

**Parámetros de búsqueda**:
- `patient` - Referencia al paciente (Patient/{id})
- `status` - Estado (planned, in-progress, finished, etc.)
- `class` - Tipo (AMB, IMP, etc.)
- `date` - Fecha del encuentro

### Observation

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/fhir/Observation` | Buscar observaciones |
| GET | `/api/fhir/Observation/{id}` | Obtener observación |
| POST | `/api/fhir/Observation` | Crear observación |

**Parámetros de búsqueda**:
- `patient` - Referencia al paciente
- `category` - Categoría (vital-signs, laboratory, imaging)
- `code` - Código LOINC
- `date` - Fecha de la observación

### Condition

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/fhir/Condition` | Buscar condiciones |
| GET | `/api/fhir/Condition/{id}` | Obtener condición |
| POST | `/api/fhir/Condition` | Crear condición |

**Parámetros de búsqueda**:
- `patient` - Referencia al paciente
- `clinical-status` - Estado clínico (active, recurrence, remission, resolved)
- `category` - Categoría

### RDA (Resumen Digital de Atención)

```bash
GET /api/fhir/$rda?patient_id={id}&encounter_id={id}
```

Genera Bundle FHIR tipo "document" con:
- Composition (estructura del documento)
- Patient
- Encounter
- Conditions (diagnósticos)
- Observations (signos vitales, exámenes)
- MedicationRequests (tratamientos)
- ServiceRequests (órdenes)
- AllergyIntolerances (alergias)

### Metadata

```bash
GET /api/fhir/metadata
```

Retorna CapabilityStatement con recursos soportados.

## 📝 Ejemplos

### Crear Paciente

```bash
curl -X POST http://localhost:8000/api/fhir/Patient \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/fhir+json" \
  -d '{
    "resourceType": "Patient",
    "identifier": [{"system": "http://hl7.org/fhir/sid/us-ssn", "value": "123456789"}],
    "name": [{"family": "Baquero", "given": ["Juan"]}],
    "gender": "male",
    "birthDate": "1985-05-15",
    "telecom": [
      {"system": "email", "value": "juan@example.com"},
      {"system": "phone", "value": "+573001234567"}
    ]
  }'
```

### Buscar Observaciones

```bash
curl -X GET "http://localhost:8000/api/fhir/Observation?patient=Patient/1&category=vital-signs" \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/fhir+json"
```

### Generar RDA

```bash
curl -X GET "http://localhost:8000/api/fhir/\$rda?patient_id=1&encounter_id=1" \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/fhir+json"
```

## 🧪 Testing

```bash
# Ejecutar tests
php artisan test --testsuite=Unit --filter=Fhir
php artisan test --testsuite=Feature --filter=Fhir

# Coverage
php artisan test --coverage
```

## 📊 Estructura de Base de Datos

### Tablas Principales

- `patients` - Pacientes con columna `fhir_resource` JSONB
- `encounters` - Encuentros clínicos
- `observations` - Observaciones clínicas
- `conditions` - Diagnósticos
- `medication_requests` - Órdenes de medicamentos
- `service_requests` - Órdenes de servicio
- `allergy_intolerances` - Alergias

### Índices

- 7 índices GIN para búsquedas en JSONB
- Índices tradicionales en columnas de búsqueda frecuente

## 🔒 Seguridad

- Autenticación con Laravel Sanctum
- Autorización por permisos (abilities)
- Rate limiting: 100 requests/minuto
- Auditoría completa de todos los requests

## 📄 Licencia

Propietario - Vespot SAS

## 📞 Soporte

Para soporte técnico, contactar a: soporte@vespot.com
