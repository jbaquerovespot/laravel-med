# Observations Specification

## Purpose

Clinical observations management for Vespot HCE providing recording of vital signs, clinical measurements, laboratory results, and other observations. Implements LOINC coding for standardization and generates FHIR R4 Observation resources.

## Requirements

### Requirement: Record Vital Signs

The system MUST allow recording vital signs during clinical encounters. The system SHALL capture at minimum: blood pressure, heart rate, respiratory rate, temperature, and oxygen saturation.

#### Scenario: Record complete vital signs

- GIVEN an encounter exists with status "in-progress"
- WHEN a practitioner records vital signs:
  - blood_pressure_systolic: 120 (mmHg)
  - blood_pressure_diastolic: 80 (mmHg)
  - heart_rate: 72 (bpm)
  - respiratory_rate: 16 (breaths/min)
  - temperature: 36.5 (°C)
  - oxygen_saturation: 98 (%)
- THEN Observation resources are created for each vital sign
- AND each is linked to the encounter
- AND each includes LOINC code and reference range

#### Scenario: Vital signs with abnormal values trigger alert

- GIVEN a practitioner is recording vital signs
- WHEN the practitioner enters:
  - blood_pressure_systolic: 180 (mmHg)
- THEN the system displays a warning: "Presión arterial elevada (Hipertensión crisis)"
- AND suggests immediate action

---

### Requirement: Reference Ranges and Interpretation

The system MUST provide reference ranges for vital signs based on patient age and gender. The system SHALL automatically flag abnormal values.

#### Scenario: Normal vital signs interpretation

- GIVEN a 35-year-old male patient
- WHEN vital signs are recorded:
  - blood_pressure: 120/80 mmHg
  - heart_rate: 72 bpm
- THEN the system interprets as "normal"
- AND displays in green

#### Scenario: Abnormal vital signs interpretation

- GIVEN a 35-year-old male patient
- WHEN vital signs are recorded:
  - blood_pressure: 150/95 mmHg
- THEN the system interprets as "high"
- AND displays in amber/yellow
- AND shows reference range (90-120/60-80 mmHg)

#### Scenario: Critical vital signs alert

- GIVEN a patient of any age
- WHEN vital signs are recorded:
  - oxygen_saturation: 85%
- THEN the system displays critical alert in red
- AND shows: "SatO2 crítica - Evaluar oxigenoterapia"

---

### Requirement: Anthropometric Measurements

The system SHOULD allow recording anthropometric measurements. The system MAY calculate derived values (BMI, body surface area).

#### Scenario: Record weight and height, calculate BMI

- GIVEN a practitioner is recording observations
- WHEN the practitioner enters:
  - weight: 70 kg
  - height: 1.75 m
- THEN the system calculates BMI: 22.86
- AND interprets BMI as "Normal" (18.5-24.9)
- AND creates Observation resources for weight, height, and BMI

#### Scenario: Pediatric growth tracking

- GIVEN a 5-year-old patient
- WHEN weight and height are recorded
- THEN the system calculates percentile
- AND displays on growth chart

---

### Requirement: Clinical Assessment Scales

The system SHOULD support standardized clinical assessment scales. The system SHALL include at minimum: Glasgow Coma Scale and pain scale.

#### Scenario: Record Glasgow Coma Scale

- GIVEN an encounter for a patient with head trauma
- WHEN the practitioner records GCS:
  - eye_response: 4 (Spontaneous)
  - verbal_response: 5 (Oriented)
  - motor_response: 6 (Obeys commands)
- THEN the system calculates total: 15
- AND interprets as "Normal"
- AND creates an Observation with LOINC code 35088-4

#### Scenario: Record pain scale

- GIVEN a patient complains of pain
- WHEN the practitioner records:
  - pain_scale: 7 (on 0-10 scale)
- THEN the system interprets as "Moderate-Severe"
- AND creates an Observation with LOINC code 72514-3

---

### Requirement: Laboratory Results Recording

The system MUST allow recording laboratory results with reference ranges. The system SHALL flag abnormal results.

#### Scenario: Record hemoglobin result

- GIVEN a laboratory service request exists
- WHEN results are entered:
  - test: "Hemoglobina"
  - loinc_code: "718-7"
  - value: 14.5
  - unit: "g/dL"
  - reference_low: 12.0
  - reference_high: 16.0
- THEN the Observation is created
- AND interpretation is "normal"
- AND linked to the service request

#### Scenario: Abnormal laboratory result alert

- GIVEN a laboratory result is entered:
  - test: "Glucosa en ayunas"
  - value: 180 mg/dL
  - reference_high: 100 mg/dL
- THEN the system flags as "high"
- AND displays alert for hyperglycemia
- AND suggests follow-up

---

### Requirement: FHIR Observation Generation

The system MUST generate FHIR R4 Observation resources for all recorded observations. The system SHALL include LOINC coding where applicable.

#### Scenario: FHIR Observation for vital signs

- GIVEN a blood pressure is recorded
- WHEN the observation is saved
- THEN a FHIR Observation resource is generated:
```json
{
  "resourceType": "Observation",
  "status": "final",
  "category": [{"coding": [{"system": "http://terminology.hl7.org/CodeSystem/observation-category", "code": "vital-signs"}]}],
  "code": {"coding": [{"system": "http://loinc.org", "code": "85354-9", "display": "Blood pressure panel"}]},
  "subject": {"reference": "Patient/{patient_id}"},
  "encounter": {"reference": "Encounter/{encounter_id}"},
  "effectiveDateTime": "{datetime}",
  "component": [
    {"code": {"coding": [{"system": "http://loinc.org", "code": "8480-6"}]}, "valueQuantity": {"value": 120, "unit": "mmHg"}},
    {"code": {"coding": [{"system": "http://loinc.org", "code": "8462-4"}]}, "valueQuantity": {"value": 80, "unit": "mmHg"}}
  ]
}
```

---

### Requirement: Observation History and Trends

The system SHOULD display observation history and trends for a patient. The system MAY generate graphical representations.

#### Scenario: View vital signs trend

- GIVEN a patient has multiple blood pressure recordings over time
- WHEN a user views the patient's vital signs history
- THEN the system displays a trend chart
- AND highlights abnormal readings
- AND allows filtering by date range

#### Scenario: Compare with previous encounter

- GIVEN a patient has a current encounter and previous encounters
- WHEN a practitioner views current vital signs
- THEN the system displays comparison with previous encounter
- AND shows changes (e.g., "PA: 120/80 → 130/85")