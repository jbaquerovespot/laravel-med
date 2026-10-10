# Medications Specification

## Purpose

Electronic prescription management for Vespot HCE providing medication ordering with allergy checking, drug interaction alerts, and PDF prescription generation. Generates FHIR R4 MedicationRequest resources and complies with Colombian prescription regulations.

## Requirements

### Requirement: Electronic Prescription

The system MUST allow creating electronic prescriptions linked to encounters. Each prescription SHALL include medication, dosage, frequency, duration, and instructions.

#### Scenario: Create prescription

- GIVEN an encounter exists with status "in-progress"
- AND a diagnosis has been recorded
- WHEN a practitioner prescribes:
  - medication_name: "Losartán 50mg"
  - dosage: "1 tablet"
  - frequency: "once daily"
  - duration: "30 days"
  - route: "oral"
  - instructions: "Tomar en la mañana con alimentos"
- THEN a MedicationRequest is created
- AND linked to the encounter and patient
- AND generates a FHIR MedicationRequest resource

#### Scenario: Prescription requires diagnosis

- GIVEN an encounter has no diagnoses recorded
- WHEN a practitioner attempts to prescribe medication
- THEN the system displays: "Debe registrar al menos un diagnóstico antes de prescribir medicamentos."

---

### Requirement: Allergy Checking

The system MUST check for patient allergies before allowing prescription. The system SHALL block or warn about allergic medications.

#### Scenario: Block prescription for known allergy

- GIVEN a patient has AllergyIntolerance to "Penicilina"
- WHEN a practitioner attempts to prescribe "Amoxicilina 500mg"
- THEN the system blocks the prescription
- AND displays: "⚠️ ALERTA DE ALERGIA: El paciente es alérgico a Penicilina. Amoxicilina está contraindicada."
- AND requires override with justification

#### Scenario: Allergy override with justification

- GIVEN a patient has allergy but practitioner determines benefit outweighs risk
- WHEN the practitioner acknowledges the allergy alert
- AND enters justification: "Paciente toleró previamente bajo supervisión"
- THEN the prescription is allowed
- AND the override is logged in the audit trail

---

### Requirement: Drug Interaction Checking

The system SHOULD check for drug-drug interactions. The system SHALL warn about significant interactions with severity classification.

#### Scenario: Warn about drug interaction

- GIVEN a patient is already taking "Warfarina"
- WHEN a practitioner prescribes "Ibuprofeno"
- THEN the system displays a warning: "⚠️ INTERACCIÓN: Warfarina + Ibuprofeno - Aumenta riesgo de sangrado. Severidad: Moderada."
- AND suggests alternatives: "Considerar Paracetamol"

#### Scenario: Contraindicated interaction

- GIVEN a patient is taking "MAOI antidepressant"
- WHEN a practitioner prescribes "Sertralina"
- THEN the system displays severe warning: "🚫 CONTRAINDICADO: Riesgo de síndrome serotoninérgico."
- AND blocks the prescription

---

### Requirement: Prescription Duration Limits

The system MUST enforce Colombian regulations for controlled medications. The system SHALL limit prescription duration for controlled substances.

#### Scenario: Controlled medication requires special authorization

- GIVEN a practitioner prescribes "Morfina 10mg"
- WHEN the duration exceeds 30 days
- THEN the system requires:
  - Special authorization code
  - Justification
- AND logs the controlled prescription

#### Scenario: Regular medication duration

- GIVEN a practitioner prescribes a non-controlled medication
- WHEN duration is set to 90 days
- THEN the prescription is accepted without special requirements

---

### Requirement: Prescription Refills

The system MUST track refill information for prescriptions. The system SHALL allow specifying number of refills allowed.

#### Scenario: Set refills for chronic medication

- GIVEN a practitioner prescribes a chronic medication
- WHEN refills_allowed is set to 3
- THEN the patient can refill the prescription 3 times
- AND each refill decrements the counter

#### Scenario: No refills for acute medication

- GIVEN a practitioner prescribes an antibiotic
- WHEN refills_allowed is set to 0
- THEN no refills are allowed
- AND a new prescription is required for additional treatment

---

### Requirement: Prescription PDF Generation

The system MUST generate printable PDF prescriptions. The PDF SHALL include all required elements for Colombian legal compliance.

#### Scenario: Generate prescription PDF

- GIVEN a prescription is created
- WHEN a practitioner clicks "Imprimir Receta"
- THEN a PDF is generated with:
  - Patient name and document number
  - Date of prescription
  - Medication name, dosage, frequency
  - Duration and quantity
  - Instructions
  - Practitioner name, license number, and signature
  - Institution name and NIT

#### Scenario: Multiple medications in one prescription

- GIVEN a patient has 3 medications prescribed
- WHEN generating the prescription PDF
- THEN all medications are listed
- AND the practitioner signs once for all

---

### Requirement: Medication Dispensing Status

The system SHOULD track medication dispensing status. The system MAY integrate with pharmacy systems in the future.

#### Scenario: Mark medication as dispensed

- GIVEN a prescription has been sent to pharmacy
- WHEN the pharmacy confirms dispensing
- THEN the medication status changes to "dispensed"
- AND the dispense date is recorded

#### Scenario: View prescription history

- GIVEN a patient has multiple prescriptions over time
- WHEN a practitioner views medication history
- THEN all prescriptions are displayed chronologically
- AND shows status (active, completed, cancelled)

---

### Requirement: FHIR MedicationRequest Generation

The system MUST generate FHIR R4 MedicationRequest resources for all prescriptions.

#### Scenario: FHIR MedicationRequest generated

- GIVEN a prescription is created
- WHEN the medication is saved
- THEN a FHIR MedicationRequest resource is generated:
```json
{
  "resourceType": "MedicationRequest",
  "id": "{request_id}",
  "status": "active",
  "intent": "order",
  "medicationCodeableConcept": {
    "coding": [{"system": "http://www.nlm.nih.gov/research/umls/rxnorm", "code": "314076", "display": "Losartan 50 MG"}]
  },
  "subject": {"reference": "Patient/{patient_id}"},
  "encounter": {"reference": "Encounter/{encounter_id}"},
  "authoredOn": "{datetime}",
  "dosageInstruction": [{
    "text": "1 tablet once daily oral for 30 days",
    "timing": {"repeat": {"frequency": 1, "period": 1, "periodUnit": "d"}},
    "route": {"coding": [{"system": "http://snomed.info/sct", "code": "26643006", "display": "Oral route"}]}
  }],
  "dispenseRequest": {"quantity": {"value": 30, "unit": "tablet"}}
}
```

---

### Requirement: Medication Search and Selection

The system MUST provide medication search functionality. The system SHOULD use RxNorm or Colombian medication database for lookup.

#### Scenario: Search medication by name

- GIVEN a practitioner is creating a prescription
- WHEN the practitioner types "los"
- THEN the system suggests:
  - Losartán 25mg
  - Losartán 50mg
  - Losartán 100mg
  - Losartán/Hidroclorotiazida 50/12.5mg

#### Scenario: Select medication populates details

- GIVEN a practitioner selects "Losartán 50mg"
- WHEN the medication is selected
- THEN the system populates:
  - Generic name
  - Dosage form
  - Common dosage
  - Route