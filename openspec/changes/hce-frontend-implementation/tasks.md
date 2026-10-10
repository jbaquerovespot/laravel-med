# Tasks: Sistema de Historias Clínicas Electrónicas (HCE) - Frontend y Flujo Clínico

## Phase 1: Foundation & Infrastructure

### 1.1 Dependencies & Configuration

- [x] 1.1.1 Install Livewire 3: `composer require livewire/livewire:^3.0`
- [x] 1.1.2 Install Spatie Permission: `composer require spatie/laravel-permission:^6.0`
- [x] 1.1.3 Install DomPDF: `composer require barryvdh/laravel-dompdf:^2.0`
- [x] 1.1.4 Publish Livewire config: `php artisan livewire:publish --config`
- [x] 1.1.5 Publish Spatie Permission config: `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"`
- [x] 1.1.6 Publish DomPDF config: `php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"`
- [x] 1.1.7 Create `config/hce.php` with session timeout (900s), rate limits (5 attempts), feature flags

### 1.2 Database Migrations

- [x] 1.2.1 Run Spatie Permission migration: creates `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` tables
- [ ] 1.2.2 Create migration `xxxx_add_role_to_users_table.php` - add default role column
- [ ] 1.2.3 Create migration `xxxx_create_patient_fingerprints_table.php` for biometric (optional, Phase 6)

### 1.3 Core Utilities

- [x] 1.3.1 Create `app/Casts/EncryptedCast.php` implementing `CastsAttributes` for AES-256 encryption
- [x] 1.3.2 Create `app/Rules/ValidColombianDocument.php` validation rule for CC/TI/PA/RC formats
- [x] 1.3.3 Create `app/Contracts/ColombianDocumentValidator.php` interface
- [x] 1.3.4 Create `app/Contracts/VitalSignsNormalizer.php` interface
- [x] 1.3.5 Create `app/Contracts/DrugInteractionChecker.php` interface
- [x] 1.3.6 Create `app/Contracts/AllergyChecker.php` interface

### 1.4 Model Modifications

- [x] 1.4.1 Modify `app/Models/User.php`: add `HasRoles` trait from Spatie Permission
- [x] 1.4.2 Modify `app/Models/Patient.php`: add `EncryptedCast` for `phone`, `email` fields
- [x] 1.4.3 Modify `app/Providers/AppServiceProvider.php`: register Observers in `boot()` method

---

## Phase 2: Authentication & Security

### 2.1 Role-Based Access Control

- [x] 2.1.1 Create `database/seeders/RolePermissionSeeder.php` with roles: admin, doctor, nurse, receptionist
- [x] 2.1.2 Define permissions in seeder: `patients.view`, `patients.create`, `patients.edit`, `encounters.create`, `medications.prescribe`, `rda.generate`
- [x] 2.1.3 Assign permissions to roles (doctor gets all clinical permissions, receptionist gets patients.view only)
- [x] 2.1.4 Create `database/seeders/UserSeeder.php` with default admin user
- [x] 2.1.5 Run seeders: `php artisan db:seed --class=RolePermissionSeeder` and `php artisan db:seed --class=UserSeeder`

### 2.2 Authentication Components

- [x] 2.2.1 Create `resources/views/layouts/auth.blade.php` layout for login page
- [x] 2.2.2 Create `app/Livewire/Auth/Login.php` component with:
  - Email/password fields
  - Rate limiting (5 attempts → 30 min lockout)
  - Session regeneration on success
  - Activity log on login/logout
- [x] 2.2.3 Create `resources/views/livewire/auth/login.blade.php` view with TailwindCSS styling
- [x] 2.2.4 Create `app/Livewire/Auth/Logout.php` component with audit logging
- [x] 2.2.5 Create `app/Http/Middleware/SessionTimeout.php` middleware for 15-minute inactivity timeout

### 2.3 Audit Observers

- [x] 2.3.1 Create `app/Observers/PatientObserver.php` with `created`, `updated`, `deleted` events logging to activity_log
- [x] 2.3.2 Create `app/Observers/EncounterObserver.php` for encounter audit logging
- [x] 2.3.3 Create `app/Observers/ConditionObserver.php` for diagnosis audit logging
- [x] 2.3.4 Create `app/Observers/MedicationRequestObserver.php` for prescription audit logging
- [x] 2.3.5 Register observers in `AppServiceProvider::boot()`

### 2.4 Routes & Middleware

- [x] 2.4.1 Modify `routes/web.php`: add routes for `/login`, `/logout`, `/dashboard`
- [x] 2.4.2 Add middleware group `web` with `auth`, `session.timeout` middleware
- [x] 2.4.3 Create route group for authenticated users with role-based access

---

## Phase 3: Patient Management

### 3.1 Layout Components

- [x] 3.1.1 Create `resources/views/layouts/app.blade.php` main layout with sidebar navigation
- [x] 3.1.2 Create `resources/views/components/alert.blade.php` reusable alert component
- [x] 3.1.3 Create `resources/views/components/modal.blade.php` reusable modal dialog component
- [x] 3.1.4 Create `resources/views/components/patient-card.blade.php` patient summary card component

### 3.2 Patient List & Search

- [x] 3.2.1 Create `app/Livewire/Patients/Index.php` component with:
  - Real-time search by document_number, name, phone
  - Pagination (20 per page)
  - Link to create/show patient
- [x] 3.2.2 Create `resources/views/livewire/patients/index.blade.php` view with search input and patient table
- [x] 3.2.3 Add route `Route::get('/patients', Patients\Index::class)->name('patients.index')`

### 3.3 Patient Creation

- [x] 3.3.1 Create `app/Livewire/Patients/Create.php` component with:
  - Form fields: document_number, first_name, last_name, birth_date, gender, email, phone, address, city, emergency_contact
  - Validation using `ValidColombianDocument` rule
  - FHIR resource generation via `FhirTransformerService`
- [x] 3.3.2 Create `resources/views/livewire/patients/create.blade.php` form view
- [x] 3.3.3 Add route `Route::get('/patients/create', Patients\Create::class)->name('patients.create')`

### 3.4 Patient Detail View

- [x] 3.4.1 Create `app/Livewire/Patients/Show.php` component displaying:
  - Patient demographics
  - Encounter history
  - Active conditions
  - Allergies
  - Medications
- [x] 3.4.2 Create `resources/views/livewire/patients/show.blade.php` detail view
- [x] 3.4.3 Add route `Route::get('/patients/{id}', Patients\Show::class)->name('patients.show')`

### 3.5 Patient Edit

- [x] 3.5.1 Create `app/Livewire/Patients/Edit.php` component with audit logging on update
- [x] 3.5.2 Create `resources/views/livewire/patients/edit.blade.php` edit form
- [x] 3.5.3 Add route `Route::get('/patients/{id}/edit', Patients\Edit::class)->name('patients.edit')`

---

## Phase 4: Clinical Workflow

### 4.1 Clinical Services

- [x] 4.1.1 Create `app/Services/Clinical/ReferenceRangeService.php` implementing `VitalSignsNormalizer`:
  - `getReferenceRange(loincCode, age, gender)` method
  - `interpret(value, loincCode, age, gender)` returning 'low'|'normal'|'high'|'critical-low'|'critical-high'
- [x] 4.1.2 Create `app/Services/Clinical/AllergyCheckService.php` implementing `AllergyChecker`:
  - `check(patient, medicationCode)` method
  - `getAllergies(patient)` method
- [x] 4.1.3 Create `app/Services/Clinical/DrugInteractionService.php` implementing `DrugInteractionChecker`:
  - `checkInteractions(medications)` method
  - `getInteraction(drug1, drug2)` method
  - SQLite database for interaction data (create `database/data/drug_interactions.sqlite`)

### 4.2 Dashboard

- [x] 4.2.1 Create `app/Livewire/Dashboard/Index.php` component with:
  - Today's appointments
  - Recent patients
  - Pending lab results
  - Quick actions (new encounter, search patient)
- [x] 4.2.2 Create `resources/views/livewire/dashboard/index.blade.php` dashboard view
- [x] 4.2.3 Add route `Route::get('/dashboard', Dashboard\Index::class)->name('dashboard')`

### 4.3 Encounter Multi-Step Form

- [x] 4.3.1 Create `app/Livewire/Encounters/Create.php` main component with step navigation:
  - `$currentStep` property (1-5)
  - `nextStep()`, `previousStep()` methods
  - `submit()` method creating Encounter + related resources
- [x] 4.3.2 Create `resources/views/livewire/encounters/create.blade.php` with step indicator and form container

### 4.4 Encounter Steps

- [x] 4.4.1 Create `app/Livewire/Encounters/Steps/PatientSelection.php`:
  - Patient search
  - Select/create patient
  - Display selected patient info
- [x] 4.4.2 Create `app/Livewire/Encounters/Steps/ReasonForVisit.php`:
  - Chief complaint text input
  - Anamnesis textarea
  - Related history
- [x] 4.4.3 Create `resources/views/components/vital-signs-input.blade.php` reusable component
- [x] 4.4.4 Create `app/Livewire/Encounters/Steps/VitalSigns.php`:
  - BP (systolic/diastolic), HR, RR, Temp, SpO2, Weight, Height
  - Calculate BMI
  - Abnormal value alerts using `ReferenceRangeService`
  - Create Observation records
- [x] 4.4.5 Create `app/Livewire/Encounters/Steps/Diagnosis.php`:
  - CIE-10 code search (create `database/data/cie10_codes.json`)
  - Principal/secondary classification
  - Create Condition records
- [x] 4.4.6 Create `app/Livewire/Encounters/Steps/Treatment.php`:
  - Medication search (RxNorm codes)
  - Allergy check via `AllergyCheckService`
  - Drug interaction check via `DrugInteractionService`
  - Dosage, frequency, duration inputs
  - Create MedicationRequest records

### 4.5 Encounter Summary & Completion

- [x] 4.5.1 Create `app/Livewire/Encounters/Show.php` component displaying encounter summary
- [x] 4.5.2 Create `resources/views/livewire/encounters/show.blade.php` encounter summary view
- [x] 4.5.3 Implement encounter status transition: in-progress → finished
- [x] 4.5.4 Add routes for encounters: create, show

### 4.6 Conditions Management

- [x] 4.6.1 Create `app/Livewire/Conditions/Form.php` standalone diagnosis form
- [x] 4.6.2 Add CIE-10 code lookup functionality

### 4.7 Medications Management

- [x] 4.7.1 Create `app/Livewire/Medications/Form.php` standalone prescription form
- [x] 4.7.2 Add allergy and interaction warnings display

### 4.8 Laboratory Orders

- [x] 4.8.1 Create `database/data/loinc_codes.json` for common lab tests
- [x] 4.8.2 Create `app/Livewire/Laboratory/Form.php` lab order form with LOINC search
- [x] 4.8.3 Add priority selection (routine, urgent, stat)
- [x] 4.8.4 Create ServiceRequest records

---

## Phase 5: RDA & Compliance

### 5.1 RDA PDF Generation

- [x] 5.1.1 Create `resources/views/rda/pdf.blade.php` RDA template with:
  - Patient header
  - Encounter info
  - Diagnoses section
  - Vital signs section
  - Medications section
  - Lab orders section
  - Practitioner signature area
- [x] 5.1.2 Create `app/Livewire/Rda/Generate.php` component:
  - Call `RdaGeneratorService::generateRda(patientId, encounterId)`
  - Generate PDF using DomPDF
  - Return download response
- [x] 5.1.3 Add route `Route::get('/rda/{encounter}', Rda\Generate::class)->name('rda.generate')`

### 5.2 Data Encryption

- [ ] 5.2.1 Create migration `xxxx_encrypt_patient_sensitive_data.php`:
  - Encrypt existing `email`, `phone` values in patients table
- [ ] 5.2.2 Run migration in production: backup first, then migrate
- [x] 5.2.3 Add feature flag `HCE_ENCRYPTION_ENABLED` to conditionally apply encryption

### 5.3 Compliance Checklist

- [x] 5.3.1 Verify audit log captures all required fields (user, IP, timestamp, action)
- [x] 5.3.2 Verify session timeout works (15 min inactivity)
- [x] 5.3.3 Verify password requirements enforced (8+ chars, uppercase, number, special)
- [x] 5.3.4 Verify soft delete on patients (no permanent deletion)
- [x] 5.3.5 Create compliance documentation in `openspec/changes/hce-frontend-implementation/COMPLIANCE.md`

---

## Phase 6: Biometric (Optional)

### 6.1 Biometric Infrastructure

- [x] 6.1.1 Create `database/migrations/xxxx_create_patient_fingerprints_table.php`:
  - `patient_id`, `finger_identifier`, `encrypted_template`, `quality_score`, `consent_date`
- [x] 6.1.2 Create `app/Services/Biometric/BiometricService.php` with:
  - `captureFingerprint(patient)` method
  - `authenticateByFingerprint(template)` method
  - Integration stub for DigitalPersona/Suprema SDK
- [x] 6.1.3 Create `app/Livewire/Biometric/Enroll.php` patient fingerprint enrollment component
- [x] 6.1.4 Create `app/Livewire/Biometric/Identify.php` patient identification by fingerprint component
- [x] 6.1.5 Add feature flag `HCE_BIOMETRIC_ENABLED` to `.env`

---

## Phase 7: Testing

### 7.1 Unit Tests

- [x] 7.1.1 Create `tests/Unit/Rules/ValidColombianDocumentTest.php`:
  - Test valid CC (8-10 digits)
  - Test valid TI (with hyphen)
  - Test valid PA (alphanumeric)
  - Test valid RC (starts with RC)
  - Test invalid formats
- [x] 7.1.2 Create `tests/Unit/Casts/EncryptedCastTest.php`:
  - Test encryption round-trip
  - Test null handling
- [x] 7.1.3 Create `tests/Unit/Services/ReferenceRangeServiceTest.php`:
  - Test adult male BP range
  - Test pediatric ranges
  - Test interpretation logic
- [x] 7.1.4 Create `tests/Unit/Services/AllergyCheckServiceTest.php`:
  - Test allergy detection
  - Test no allergy case
- [x] 7.1.5 Create `tests/Unit/Services/DrugInteractionServiceTest.php`:
  - Test known interaction (warfarin + NSAID)
  - Test no interaction case

### 7.2 Observer Tests

- [x] 7.2.1 Create `tests/Unit/Observers/PatientObserverTest.php`:
  - Test created event logs to activity_log
  - Test updated event logs changes
  - Test deleted event logs soft delete

### 7.3 Livewire Component Tests

- [x] 7.3.1 Create `tests/Feature/Livewire/Auth/LoginTest.php`:
  - Test successful login
  - Test failed login with wrong password
  - Test rate limiting (5 attempts → lockout)
- [x] 7.3.2 Create `tests/Feature/Livewire/Patients/IndexTest.php`:
  - Test patient search by document
  - Test patient search by name
  - Test empty results display
- [x] 7.3.3 Create `tests/Feature/Livewire/Patients/CreateTest.php`:
  - Test successful patient creation
  - Test validation errors
  - Test duplicate document rejection
- [x] 7.3.4 Create `tests/Feature/Livewire/Patients/ShowTest.php`:
  - Test patient detail display
  - Test encounter history display
- [x] 7.3.5 Create `tests/Feature/Livewire/Encounters/CreateTest.php`:
  - Test step navigation
  - Test vital signs recording
  - Test diagnosis creation
  - Test medication prescription with allergy check
  - Test encounter completion

### 7.4 Integration Tests

- [x] 7.4.1 Create `tests/Feature/RbacTest.php`:
  - Test doctor can create encounter
  - Test receptionist cannot prescribe
  - Test admin can manage users
- [x] 7.4.2 Create `tests/Feature/SessionTimeoutTest.php`:
  - Test session expires after 15 min
  - Test session refreshes on activity

### 7.5 Feature Tests

- [x] 7.5.1 Create `tests/Feature/FullEncounterFlowTest.php`:
  - Login as doctor
  - Create patient
  - Create encounter with vitals, diagnosis, prescription
  - Verify FHIR resources generated
  - Generate RDA PDF
- [x] 7.5.2 Create `tests/Feature/RdaGenerationTest.php`:
  - Test RDA contains all sections
  - Test PDF generation success
  - Test PDF download response

---

## Task Summary

| Phase | Tasks | Focus |
|-------|-------|-------|
| Phase 1 | 16 | Foundation & Infrastructure |
| Phase 2 | 15 | Authentication & Security |
| Phase 3 | 15 | Patient Management |
| Phase 4 | 26 | Clinical Workflow |
| Phase 5 | 11 | RDA & Compliance |
| Phase 6 | 5 | Biometric (Optional) |
| Phase 7 | 16 | Testing |
| **Total** | **104** | |

---

## Implementation Order

The recommended implementation order follows the phase numbering:

1. **Phase 1** (Foundation) must complete first - provides config, migrations, and core utilities
2. **Phase 2** (Auth) depends on Phase 1 - establishes security before adding features
3. **Phase 3** (Patients) depends on Phase 2 - requires authenticated users
4. **Phase 4** (Clinical) depends on Phase 3 - patients must exist for encounters
5. **Phase 5** (RDA) depends on Phase 4 - needs clinical data for RDA generation
6. **Phase 6** (Biometric) is optional - can be deferred or skipped
7. **Phase 7** (Testing) runs throughout - tests should be written alongside code

---

## Notes

- All Livewire components should use `#[Locked]` for properties that shouldn't be modified from frontend
- Use Laravel's `encrypt()` for sensitive data - never roll custom encryption
- All audit logs must include: user_id, action, timestamp, IP, affected model/id
- FHIR resources are generated automatically via existing `FhirTransformerService` - no need to manually create FHIR JSON