# AGENTS.md - Development Guide for Laravel FHIR API

## 🚀 Build & Development Commands

```bash
composer install                          # Install dependencies
php artisan serve                         # Start dev server (localhost:8000)
composer run dev                          # Concurrent: server, queue, logs, vite
php artisan migrate                       # Run migrations
php artisan migrate:rollback              # Rollback last batch
php artisan config:cache                  # Cache config (production)
php artisan cache:clear                   # Clear all caches
```

## 🧪 Testing Commands

```bash
php artisan test                          # Run all tests
php artisan test --coverage               # With coverage
php artisan test --testsuite=Unit         # Unit tests only
php artisan test --testsuite=Feature      # Feature tests only

# Run single test (IMPORTANT)
php artisan test tests/Unit/Fhir/Transformers/PatientToFhirTransformerTest.php
php artisan test --filter=testTransformPatientWithAllFields
php artisan test --filter=Fhir            # All FHIR tests
php artisan test --stop-on-failure        # Stop at first failure
```

## 📝 Code Style Guidelines

### File Structure
```php
<?php
namespace App\Services\Fhir;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class FhirTransformerService
{
    protected array $transformers = [];
    
    public function __construct() { }
    
    public function transform(): array { }
    
    protected function getTransformer(): object { }
}
```

### Naming Conventions
- **Classes**: PascalCase (`FhirTransformerService`, `PatientToFhirTransformer`)
- **Methods**: camelCase (`transformToBundle`, `extractResourceId`)
- **Variables**: camelCase (`$fhirResource`, `$patientId`)
- **Constants**: UPPER_SNAKE_CASE (`FHIR_VERSION`, `MAX_PAGE_SIZE`)
- **Database**: snake_case (`patients`, `document_number`, `birth_date`)

### Type Declarations
```php
declare(strict_types=1);

public function transform(object $model, string $resourceType): array
public function findById(?int $id): ?Patient
public function getValue(): int|float|string
```

### Import Guidelines
```php
// Group by type, alphabetically, blank line between groups
use App\Models\Patient;
use App\Models\Encounter;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;

use InvalidArgumentException;
use Exception;
```

### Error Handling
```php
// Try-catch for external operations
try {
    $this->validator->validate($resource);
} catch (ValidationException $e) {
    return OperationOutcomeResponse::error($e->getMessage(), 400);
}

// Throw specific exceptions
throw new InvalidArgumentException('Expected Patient model');
throw new NotFoundException("Resource not found: {$id}");

// Laravel helpers
abort_if(!$user, 403, 'Unauthorized access');
abort_unless($resource, 404, 'Resource not found');
```

### Laravel Conventions

#### Controllers
```php
class FhirPatientController extends Controller
{
    public function __construct(
        protected FhirTransformerService $transformer,
        protected FhirValidatorService $validator
    ) {}
    
    public function search(Request $request): JsonResponse
    public function show(int $id): JsonResponse
}
```

#### Models
```php
class Patient extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = ['document_number', 'first_name', 'last_name'];
    
    protected $casts = [
        'birth_date' => 'date',
        'active' => 'boolean',
        'fhir_resource' => 'array',
    ];
    
    public function encounters(): HasMany
    public function getFullNameAttribute(): string
}
```

#### Middleware
```php
class FhirAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$token = $request->bearerToken()) {
            return $this->unauthorizedResponse('Missing token');
        }
        return $next($request);
    }
}
```

### PHPDoc
```php
/**
 * Transform Patient model to FHIR resource.
 *
 * @param  Patient  $patient  Patient model instance
 * @return array FHIR R4 Patient resource
 * @throws InvalidArgumentException If model type is incorrect
 */
public function transform(object $patient): array
```

### FHIR Guidelines
```php
return [
    'resourceType' => 'Patient',
    'id' => (string) $patient->id,
    'meta' => [
        'versionId' => (string) $patient->updated_at->timestamp,
        'lastUpdated' => $patient->updated_at->toIso8601String(),
    ],
];

// Validation (3 levels: structure → schema → business rules)
$result = $this->validator->validate($resource, 'Patient');
if ($result->isInvalid()) {
    return OperationOutcomeResponse::error($result->getErrorMessages(), 400);
}
```

## 🔧 Environment Setup

```env
APP_ENV=local
APP_DEBUG=true
APP_KEY=base64:...

DB_CONNECTION=pgsql
DB_HOST=devops
DB_PORT=5432
DB_DATABASE=medhis
DB_USERNAME=medhis
DB_PASSWORD=medhis
DB_SCHEMA=medhis

FHIR_ENABLED=true
FHIR_MAX_PAGE_SIZE=100
```

## 📋 Project Structure

```
laravel-med/
├── app/
│   ├── Http/Controllers/Fhir/     # FHIR controllers
│   ├── Http/Middleware/           # FHIR middleware
│   ├── Models/                    # Eloquent models
│   └── Services/Fhir/             # FHIR services & transformers
├── database/migrations/
├── routes/
│   ├── api.php                    # API routes
│   └── fhir.php                   # FHIR-specific routes
├── tests/
│   ├── Unit/Fhir/                 # Unit tests
│   └── Feature/Fhir/              # Integration tests
└── openspec/changes/              # SDD documentation
```

## ⚠️ Important Notes

1. **Always run migrations** after pulling: `php artisan migrate`
2. **Clear cache** when configs change: `php artisan config:clear`
3. **Use transactions** in tests: `use RefreshDatabase`
4. **Follow FHIR R4 spec** for resource structure
5. **All FHIR endpoints** require Bearer token authentication
6. **Audit logging** is automatic via middleware
7. **Rate limiting**: 100 requests/minute per user

## 📚 References

- Laravel Docs: https://laravel.com/docs
- FHIR R4 Spec: https://hl7.org/fhir/R4/
- Project README: `/README-FHIR.md`
- Test Report: `/openspec/changes/fhir-service-implementation/FHIR-API-TEST-REPORT.md`
