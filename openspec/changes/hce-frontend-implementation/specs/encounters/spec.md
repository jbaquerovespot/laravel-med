# Encounters Specification

## Purpose

Clinical encounter management for Vespot HCE providing complete recording of medical consultations including reason for visit, anamnesis, physical examination, diagnoses, treatments, and follow-up. Each encounter generates FHIR R4 compliant resources and contributes to the patient's clinical history.

## Requirements

### Requirement: Create Clinical Encounter

The system MUST allow creating clinical encounters linked to a patient. Each encounter SHALL capture the reason for visit, start datetime, and attending practitioner.

#### Scenario: Create new encounter for patient

- GIVEN a patient exists with document number "1234567890"
- AND a practitioner is logged in
- WHEN the practitioner creates an encounter with:
  - patient_id: {patient_id}
  - type: "consultation"
  - reason_text: "Dolor de cabeza persistente"
  - start_datetime: "2026-03-17T10:00:00"
- THEN the encounter is created with status "in-progress"
- AND linked to the patient and practitioner
- AND a FHIR Encounter resource is generated

#### Scenario: Encounter requires patient

- GIVEN a practitioner is creating an encounter
- WHEN the practitioner submits without selecting a patient
- THEN the system rejects the submission
- AND displays "Debe seleccionar un paciente para la consulta."

---

### Requirement: Anamnesis and Medical History

The system MUST allow recording anamnesis (patient history) including current illness, symptoms, and relevant medical history. The system SHALL support structured and free-text entry.

#### Scenario: Record anamnesis

- GIVEN an encounter exists with status "in-progress"
- WHEN the practitioner records anamnesis:
  - current_illness: "Dolor de cabeza frontal, 3 días de evolución"
  - symptoms: ["cefalea", "nausea"]
  - relevant_history: "Antecedente de migraña"
- THEN the anamnesis is saved to the encounter
- AND displayed in the encounter summary

#### Scenario: Load previous encounters

- GIVEN a patient has previous encounters
- WHEN the practitioner views the current encounter
- THEN the system displays a sidebar with previous encounter summaries
- AND allows quick access to relevant history

---

### Requirement: Physical Examination

The system MUST allow recording physical examination findings. The system SHALL support structured examination templates by specialty.

#### Scenario: Record physical examination

- GIVEN an encounter exists with status "in-progress"
- WHEN the practitioner records examination:
  - general_appearance: "Paciente en buen estado general"
  - vital_signs: {blood_pressure: "120/80", heart_rate: "72", temperature: "36.5"}
  - head_neck: "Normocéfalo, sin adenopatías"
  - cardiovascular: "Ruidos cardíacos rítmicos"
  - respiratory: "Murmullo vesicular conservado"
  - abdomen: "Blando, depresible, no doloroso"
- THEN the examination is saved
- AND vital signs are linked as Observations

---

### Requirement: Encounter Status Management

The system MUST track encounter status through the clinical workflow. Status SHALL transition: in-progress → finished (or cancelled).

#### Scenario: Finish encounter

- GIVEN an encounter exists with status "in-progress"
- AND has at least one diagnosis or treatment
- WHEN the practitioner clicks "Finalizar Consulta"
- THEN the encounter status changes to "finished"
- AND end_datetime is set to current time
- AND the encounter is locked for editing

#### Scenario: Cancel encounter

- GIVEN an encounter exists with status "in-progress"
- WHEN the practitioner clicks "Cancelar Consulta"
- AND provides a cancellation reason
- THEN the encounter status changes to "cancelled"
- AND the cancellation reason is recorded

#### Scenario: Cannot edit finished encounter

- GIVEN an encounter has status "finished"
- WHEN a user attempts to modify the encounter
- THEN the system rejects the modification
- AND displays "Esta consulta ya fue finalizada y no puede modificarse."

---

### Requirement: Encounter Linking to Clinical Resources

The system MUST link encounters to related clinical resources (conditions, observations, medications, service requests). The system SHALL maintain referential integrity.

#### Scenario: Link diagnosis to encounter

- GIVEN an encounter exists
- WHEN a practitioner adds a diagnosis with code "R51" (Cefalea)
- THEN the Condition is created and linked to the encounter
- AND appears in the encounter's diagnoses list

#### Scenario: Link vital signs to encounter

- GIVEN an encounter exists
- WHEN vital signs are recorded (PA, FC, FR, T°, SatO2)
- THEN Observation resources are created
- AND linked to the encounter
- AND display in the encounter's observations section

---

### Requirement: Encounter Summary and RDA Generation

The system MUST generate a summary of the encounter. The system SHALL support generating the RDA (Resumen Digital de Atención) as required by Resolución 1888 de 2025.

#### Scenario: View encounter summary

- GIVEN an encounter is finished
- WHEN a user views the encounter summary
- THEN the system displays:
  - Patient demographics
  - Reason for visit
  - Anamnesis
  - Physical examination
  - Diagnoses (with CIE-10/SNOMED codes)
  - Medications prescribed
  - Exams ordered
  - Follow-up instructions

#### Scenario: Generate RDA PDF

- GIVEN an encounter is finished
- WHEN a user clicks "Generar RDA"
- THEN the system generates a PDF document containing:
  - Patient identifier and demographics
  - Encounter date and practitioner
  - Complete clinical summary
  - Diagnoses with codes
  - Treatments prescribed
- AND the PDF is downloadable
- AND includes digital signature placeholder

---

### Requirement: Encounter Audit Trail

The system MUST maintain an audit trail for all encounter operations. The system SHALL log creation, modifications, and access to comply with Resolución 1995.

#### Scenario: Audit encounter creation

- GIVEN a practitioner creates an encounter
- THEN an audit log entry is created with:
  - event: "encounter.created"
  - patient_id: {patient_id}
  - encounter_id: {encounter_id}
  - practitioner_id: {practitioner_id}
  - timestamp: {iso_datetime}

#### Scenario: Audit encounter access

- GIVEN a user views an encounter
- THEN an audit log entry is created with:
  - event: "encounter.viewed"
  - encounter_id: {encounter_id}
  - user_id: {user_id}
  - timestamp: {iso_datetime}