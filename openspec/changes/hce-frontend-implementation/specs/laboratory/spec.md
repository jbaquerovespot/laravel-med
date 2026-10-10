# Laboratory Specification

## Purpose

Laboratory and diagnostic exam management for Vespot HCE providing exam ordering, result recording, and value interpretation. Generates FHIR R4 ServiceRequest and Observation resources with LOINC coding.

## Requirements

### Requirement: Order Laboratory Exams

The system MUST allow ordering laboratory and diagnostic exams linked to encounters. Each order SHALL include exam type, priority, and clinical indication.

#### Scenario: Order complete blood count

- GIVEN an encounter exists with status "in-progress"
- WHEN a practitioner orders:
  - exam_type: "laboratory"
  - code: "58410-2" (LOINC for CBC)
  - display: "Hemograma completo"
  - priority: "routine"
  - clinical_indication: "Control de rutina"
- THEN a ServiceRequest is created
- AND status is "active"
- AND linked to the patient and encounter

#### Scenario: Order with urgent priority

- GIVEN a patient presents with acute symptoms
- WHEN a practitioner orders with priority "stat"
- THEN the ServiceRequest is flagged as urgent
- AND displays with red indicator
- AND triggers notification (future feature)

---

### Requirement: Exam Types and Coding

The system MUST use LOINC codes for laboratory tests. The system SHOULD provide a searchable exam catalog.

#### Scenario: Search exam catalog

- GIVEN a practitioner is ordering exams
- WHEN the practitioner searches "hemoglobina"
- THEN the system displays matching exams:
  - Hemoglobina [Masa/Volumen] (LOINC 718-7)
  - Hemoglobina glicada (LOINC 4548-4)
  - Hemoglobina en sangre arterial (LOINC 30313-1)

#### Scenario: Custom exam entry

- GIVEN a needed exam is not in the catalog
- WHEN a practitioner enters a custom exam name
- THEN the system creates the ServiceRequest with:
  - code: "custom"
  - display: {custom_name}
  - note: "Examen no codificado"

---

### Requirement: Record Exam Results

The system MUST allow recording exam results with values, units, and reference ranges. The system SHALL flag abnormal results.

#### Scenario: Record hemoglobin result

- GIVEN a ServiceRequest exists for hemoglobin
- WHEN results are entered:
  - value: 14.5
  - unit: "g/dL"
  - reference_low: 12.0
  - reference_high: 16.0
- THEN an Observation is created
- AND interpretation is "normal"
- AND the ServiceRequest status changes to "completed"

#### Scenario: Flag abnormal result

- GIVEN a result is entered:
  - test: "Glucosa en ayunas"
  - value: 250 mg/dL
  - reference_high: 100 mg/dL
- THEN the system flags as "critical-high"
- AND displays in red
- AND shows alert for clinical follow-up

---

### Requirement: Reference Range Management

The system MUST provide reference ranges based on patient demographics. The system SHOULD support age and gender-specific ranges.

#### Scenario: Pediatric reference range

- GIVEN a 2-year-old patient
- WHEN hemoglobin is recorded
- THEN the system uses pediatric reference range: 10.5-13.5 g/dL
- AND interpretation is based on this range

#### Scenario: Gender-specific reference

- GIVEN a female adult patient
- WHEN hemoglobin is recorded
- THEN the system uses female reference range: 12.0-15.5 g/dL

---

### Requirement: Multi-Component Results

The system MUST support panel results with multiple components. The system SHALL display all components together.

#### Scenario: Record complete metabolic panel

- GIVEN a CMP is ordered
- WHEN results are entered:
  - Glucose: 95 mg/dL (normal)
  - BUN: 18 mg/dL (normal)
  - Creatinine: 1.0 mg/dL (normal)
  - Sodium: 140 mEq/L (normal)
  - Potassium: 4.0 mEq/L (normal)
  - Chloride: 102 mEq/L (normal)
- THEN Observations are created for each component
- AND grouped under a parent Observation (the panel)
- AND the panel status is "final"

---

### Requirement: Result Attachments

The system SHOULD allow attaching files to exam results. The system MAY support PDF reports and images.

#### Scenario: Attach PDF report

- GIVEN a laboratory provides a PDF report
- WHEN the result is recorded
- AND a PDF is uploaded
- THEN the PDF is attached to the ServiceRequest
- AND can be downloaded from the results view

#### Scenario: Image attachment for imaging studies

- GIVEN an imaging study (X-ray, CT) is ordered
- WHEN the radiologist uploads the image
- THEN the image is attached as a Media resource
- AND linked to the ServiceRequest

---

### Requirement: Result Notification

The system SHOULD notify practitioners when results are available. The system SHALL flag critical values for immediate attention.

#### Scenario: Critical value notification

- GIVEN a result has critical value (e.g., K+ 7.0 mEq/L)
- WHEN the result is recorded
- THEN the ordering practitioner is flagged for notification
- AND the result appears in a critical alerts queue
- AND requires acknowledgment

---

### Requirement: Result History and Trends

The system MUST display result history for the same test over time. The system SHALL provide trend visualization.

#### Scenario: View hemoglobin trend

- GIVEN a patient has multiple hemoglobin tests over 6 months
- WHEN a practitioner views the hemoglobin result
- THEN a trend graph is displayed
- AND shows all values with dates
- AND highlights abnormal values

#### Scenario: Compare with previous result

- GIVEN a current result and previous result exist
- WHEN viewing the current result
- THEN the system shows comparison:
  - "Glucosa: 95 mg/dL (prev: 110 mg/dL, -14%)"

---

### Requirement: FHIR ServiceRequest Generation

The system MUST generate FHIR R4 ServiceRequest resources for all exam orders.

#### Scenario: FHIR ServiceRequest generated

- GIVEN an exam is ordered
- WHEN the ServiceRequest is saved
- THEN a FHIR ServiceRequest resource is generated:
```json
{
  "resourceType": "ServiceRequest",
  "id": "{request_id}",
  "status": "active",
  "intent": "order",
  "category": [{"coding": [{"system": "http://snomed.info/sct", "code": "108252007", "display": "Laboratory procedure"}]}],
  "code": {"coding": [{"system": "http://loinc.org", "code": "58410-2", "display": "Complete blood count"}]},
  "subject": {"reference": "Patient/{patient_id}"},
  "encounter": {"reference": "Encounter/{encounter_id}"},
  "authoredOn": "{datetime}",
  "priority": "routine"
}
```

---

### Requirement: Imaging Studies

The system MUST support ordering and recording imaging studies. The system SHALL use appropriate imaging codes (LOINC/RadLex).

#### Scenario: Order chest X-ray

- GIVEN an encounter exists
- WHEN a practitioner orders:
  - exam_type: "imaging"
  - code: "24627-2" (LOINC for chest X-ray)
  - display: "Radiografía de tórax PA y lateral"
  - clinical_indication: "Tos persistente, descartar neumonía"
- THEN a ServiceRequest is created with category "imaging"

#### Scenario: Record imaging findings

- GIVEN an imaging ServiceRequest exists
- WHEN the radiologist enters findings:
  - impression: "Sin hallazgos patológicos"
  - recommendation: "Control en 6 meses"
- THEN the findings are recorded
- AND linked to the ServiceRequest