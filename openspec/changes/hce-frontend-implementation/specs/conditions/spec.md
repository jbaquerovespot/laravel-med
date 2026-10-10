# Conditions Specification

## Purpose

Clinical diagnosis management for Vespot HCE providing recording of patient conditions with standardized coding (CIE-10 and SNOMED CT). Tracks diagnosis status, onset, and resolution. Generates FHIR R4 Condition resources.

## Requirements

### Requirement: Record Clinical Diagnosis

The system MUST allow recording diagnoses with standardized codes. The system SHALL support CIE-10 (ICD-10) and SNOMED CT coding systems.

#### Scenario: Add diagnosis with CIE-10 code

- GIVEN an encounter exists with status "in-progress"
- WHEN a practitioner adds a diagnosis:
  - code_system: "ICD-10"
  - code: "I10"
  - display: "Hipertensión esencial (primaria)"
  - clinical_status: "active"
- THEN a Condition resource is created
- AND linked to the patient and encounter
- AND displays in the encounter's diagnosis list

#### Scenario: Add diagnosis with SNOMED CT code

- GIVEN an encounter exists
- WHEN a practitioner adds a diagnosis:
  - code_system: "SNOMED-CT"
  - code: "38341003"
  - display: "Hypertensive disorder, systemic arterial"
  - clinical_status: "active"
- THEN a Condition resource is created with SNOMED coding

---

### Requirement: CIE-10 Code Validation

The system MUST validate CIE-10 codes against the standard format. The system SHALL only accept valid CIE-10 codes.

#### Scenario: Valid CIE-10 code accepted

- GIVEN a practitioner is adding a diagnosis
- WHEN the code entered matches pattern: letter + 2 digits + optional .digits
  - "I10", "E11.9", "J45.909"
- THEN the system accepts the code
- AND looks up the display name

#### Scenario: Invalid CIE-10 code rejected

- GIVEN a practitioner is adding a diagnosis
- WHEN the code entered is invalid:
  - "ABC123" (wrong format)
  - "I101" (non-existent code)
- THEN the system rejects the code
- AND displays "Código CIE-10 inválido"

---

### Requirement: Diagnosis Classification

The system MUST classify diagnoses as principal or secondary. The system SHALL allow only one principal diagnosis per encounter.

#### Scenario: Set principal diagnosis

- GIVEN an encounter has no principal diagnosis
- WHEN a practitioner marks diagnosis "I10" as principal
- THEN the diagnosis is flagged as principal
- AND appears first in the diagnosis list
- AND is used for RDA generation

#### Scenario: Change principal diagnosis

- GIVEN an encounter has principal diagnosis "I10"
- WHEN a practitioner marks "E11.9" as principal
- THEN "E11.9" becomes the principal diagnosis
- AND "I10" is demoted to secondary

---

### Requirement: Diagnosis Clinical Status

The system MUST track the clinical status of each condition. The system SHALL support: active, recurrence, remission, resolved.

#### Scenario: Active condition

- GIVEN a condition is recorded
- WHEN clinical_status is "active"
- THEN the condition appears in the patient's active problem list
- AND requires follow-up

#### Scenario: Resolve condition

- GIVEN a condition has clinical_status "active"
- WHEN a practitioner marks it as "resolved"
- AND provides abatement date
- THEN the condition moves to resolved conditions
- AND no longer appears in active problem list

#### Scenario: Recurrence of resolved condition

- GIVEN a condition was resolved 6 months ago
- WHEN the patient presents with the same condition again
- THEN the practitioner can mark clinical_status as "recurrence"
- AND the system links to the previous condition record

---

### Requirement: Verification Status

The system SHOULD track the verification status of diagnoses. The system SHALL support: confirmed, provisional, differential, unconfirmed.

#### Scenario: Provisional diagnosis

- GIVEN a practitioner suspects a diagnosis but needs tests
- WHEN the diagnosis is added with verification_status "provisional"
- THEN the system displays "Diagnóstico provisional"
- AND flags for follow-up confirmation

#### Scenario: Confirm diagnosis after tests

- GIVEN a diagnosis has verification_status "provisional"
- WHEN lab results confirm the diagnosis
- AND the practitioner updates to "confirmed"
- THEN the verification_status changes
- AND the audit log records the confirmation

---

### Requirement: Problem List Management

The system MUST maintain an active problem list for each patient. The problem list SHALL aggregate all active conditions across encounters.

#### Scenario: View patient problem list

- GIVEN a patient has multiple active conditions from different encounters
- WHEN a practitioner views the patient's problem list
- THEN all active conditions are displayed
- AND grouped by category (chronic, acute)
- AND sorted by date added

#### Scenario: Add chronic condition to problem list

- GIVEN a patient has a new chronic condition diagnosed
- WHEN the condition is recorded with category "problem-list-item"
- THEN it appears in the patient's permanent problem list
- AND is flagged as chronic

---

### Requirement: FHIR Condition Generation

The system MUST generate FHIR R4 Condition resources for all diagnoses. The system SHALL include all required FHIR fields.

#### Scenario: FHIR Condition generated

- GIVEN a diagnosis is recorded
- WHEN the condition is saved
- THEN a FHIR Condition resource is generated:
```json
{
  "resourceType": "Condition",
  "id": "{condition_id}",
  "clinicalStatus": {"coding": [{"system": "http://terminology.hl7.org/CodeSystem/condition-clinical", "code": "active"}]},
  "verificationStatus": {"coding": [{"system": "http://terminology.hl7.org/CodeSystem/condition-ver-status", "code": "confirmed"}]},
  "category": [{"coding": [{"system": "http://terminology.hl7.org/CodeSystem/condition-category", "code": "encounter-diagnosis"}]}],
  "code": {"coding": [{"system": "http://hl7.org/fhir/sid/icd-10", "code": "I10", "display": "Essential hypertension"}]},
  "subject": {"reference": "Patient/{patient_id}"},
  "encounter": {"reference": "Encounter/{encounter_id}"},
  "onsetDateTime": "{onset_date}",
  "recordedDate": "{recorded_date}"
}
```

---

### Requirement: Allergy Recording

The system MUST allow recording patient allergies. Allergies SHALL be recorded as AllergyIntolerance resources with appropriate criticality.

#### Scenario: Record drug allergy

- GIVEN a patient has a known drug allergy
- WHEN a practitioner records:
  - substance: "Penicilina"
  - reaction: "Urticaria, anafilaxia"
  - criticality: "high"
- THEN an AllergyIntolerance resource is created
- AND appears in the patient's allergy list
- AND triggers alerts when prescribing medications

#### Scenario: Allergy alert on prescription

- GIVEN a patient has allergy to "Penicilina"
- WHEN a practitioner prescribes "Amoxicilina"
- THEN the system displays an alert: "ALERGA: El paciente tiene alergia a Penicilina"
- AND requires acknowledgment before proceeding