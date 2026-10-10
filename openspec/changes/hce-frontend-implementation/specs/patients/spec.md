# Patients Specification

## Purpose

Patient management system for Vespot HCE providing CRUD operations for patient records, using Colombian document numbers as unique identifiers. Implements patient search functionality and maintains FHIR R4 compliance for interoperability.

## Requirements

### Requirement: Unique Patient Identification by Document

The system MUST use the Colombian document number (cédula) as the unique identifier for patients. The system SHALL validate document format according to Colombian standards.

#### Scenario: Create patient with valid cédula

- GIVEN no patient exists with document number "1234567890"
- WHEN a user creates a patient with:
  - document_number: "1234567890"
  - first_name: "Juan"
  - last_name: "Pérez"
  - birth_date: "1985-05-15"
  - gender: "male"
- THEN the patient is created
- AND the FHIR Patient resource is generated
- AND stored in fhir_resource field

#### Scenario: Reject duplicate document number

- GIVEN a patient exists with document number "1234567890"
- WHEN a user attempts to create a patient with document number "1234567890"
- THEN the system rejects the creation
- AND displays "Ya existe un paciente con este número de documento."

#### Scenario: Valid Colombian document formats

- GIVEN a user is creating a patient
- WHEN the user enters document number:
  - "12345678" (8 digits - cédula)
  - "1234567890" (10 digits - cédula)
  - "123456-7" (TI format)
  - "AB123456" (passport format)
- THEN the system accepts the document number

---

### Requirement: Patient Search Functionality

The system MUST allow searching patients by document number, name, or phone. The system SHALL provide real-time search results as the user types.

#### Scenario: Search by document number

- GIVEN patients exist with documents "1234567890" and "0987654321"
- WHEN the user searches for "12345"
- THEN the system displays patient with document "1234567890"
- AND shows full name and birth date in results

#### Scenario: Search by name

- GIVEN a patient exists with name "Juan Pérez García"
- WHEN the user searches for "Juan Pérez"
- THEN the system displays matching patients
- AND results are ordered by relevance

#### Scenario: Search by phone

- GIVEN a patient exists with phone "3001234567"
- WHEN the user searches for "300123"
- THEN the system displays the matching patient

#### Scenario: No results found

- GIVEN no patients match the search criteria
- WHEN the user searches for "XYZ123"
- THEN the system displays "No se encontraron pacientes"
- AND shows a button to create a new patient

---

### Requirement: Patient Personal Information

The system MUST capture complete patient personal information. The system SHALL store at minimum: document number, names, birth date, gender, contact information, and emergency contact.

#### Scenario: Create patient with all fields

- GIVEN a user is creating a patient
- WHEN the user fills all fields:
  - document_number, first_name, last_name, birth_date, gender
  - email, phone, address, city
  - blood_type, rh_factor
  - emergency_contact_name, emergency_contact_phone
  - insurance_company, insurance_number
- THEN the patient is created with all information
- AND the FHIR resource includes all provided data

#### Scenario: Required fields validation

- GIVEN a user is creating a patient
- WHEN the user submits without required fields (document_number, first_name, last_name, birth_date, gender)
- THEN the system rejects the submission
- AND displays "Los siguientes campos son obligatorios: {field_names}"

---

### Requirement: Patient Update and History

The system MUST allow updating patient information. The system SHALL maintain an audit trail of all changes to patient records.

#### Scenario: Update patient phone number

- GIVEN a patient exists with phone "3001234567"
- WHEN a user updates the phone to "3009876543"
- THEN the patient record is updated
- AND an audit log entry is created with the change
- AND the FHIR resource is regenerated

#### Scenario: View patient history

- GIVEN a patient has been modified multiple times
- WHEN a user views the patient's change history
- THEN the system displays a chronological list of changes
- AND shows: timestamp, user who made the change, fields modified

---

### Requirement: Patient Soft Delete

The system MUST implement soft delete for patient records. The system SHALL NOT permanently delete patient data to comply with Resolución 1995 (20-year retention).

#### Scenario: Soft delete a patient

- GIVEN a patient exists with document number "1234567890"
- WHEN an admin soft deletes the patient
- THEN the patient is marked as deleted
- AND the patient no longer appears in search results
- AND the patient data is retained in the database

#### Scenario: Restore soft-deleted patient

- GIVEN a patient was soft deleted
- WHEN an admin restores the patient
- THEN the patient appears in search results again
- AND the audit log records the restoration

---

### Requirement: FHIR Resource Generation

The system MUST generate and store a FHIR R4 Patient resource for each patient. The system SHALL keep the FHIR resource synchronized with the patient record.

#### Scenario: FHIR resource generated on creation

- GIVEN a new patient is created
- WHEN the patient is saved
- THEN a FHIR R4 Patient resource is generated with:
  - resourceType: "Patient"
  - id: {patient_id}
  - identifier: [{system: "http://co.identification", value: {document_number}}]
  - name: [{family: {last_name}, given: [{first_name}]}]
  - gender: {gender}
  - birthDate: {birth_date}
- AND stored in the fhir_resource JSONB field

#### Scenario: FHIR resource updated on patient change

- GIVEN a patient's FHIR resource exists
- WHEN the patient's name is updated
- THEN the FHIR resource is regenerated
- AND stored with updated name

---

### Requirement: Blood Type and Emergency Contact

The system SHOULD capture blood type and emergency contact information. This information MAY be displayed prominently in the patient's clinical record.

#### Scenario: Record blood type

- GIVEN a user is creating or editing a patient
- WHEN the user enters blood_type: "O+" and rh_factor: "+"
- THEN the system stores the blood type
- AND displays it in the patient header

#### Scenario: Emergency contact required for new patients

- GIVEN a user is creating a patient
- WHEN the user submits without emergency contact
- THEN the system displays a warning (not blocking)
- AND suggests adding emergency contact for safety