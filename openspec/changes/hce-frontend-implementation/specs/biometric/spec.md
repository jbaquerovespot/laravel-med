# Biometric Specification

## Purpose

Biometric authentication support for Vespot HCE providing fingerprint-based patient identification and practitioner authentication. Implements secure storage of biometric templates in compliance with Ley 1581 de 2012 (Colombian data protection law).

## Requirements

### Requirement: Fingerprint Capture for Patients

The system MUST allow capturing fingerprint templates for patient identification. The system SHALL store only encrypted templates, never raw fingerprint images.

#### Scenario: Capture patient fingerprint

- GIVEN a patient exists without fingerprint
- AND a biometric device is connected
- WHEN an authorized user initiates fingerprint capture
- THEN the system prompts for fingerprint scan
- AND extracts a template (not the image)
- AND encrypts the template
- AND stores in fingerprint_template field

#### Scenario: Fingerprint capture requires consent

- GIVEN a patient is being registered
- WHEN fingerprint capture is initiated
- THEN the system displays consent form
- AND requires patient signature or acknowledgment
- AND logs consent in audit trail

---

### Requirement: Patient Identification by Fingerprint

The system MUST allow identifying patients by fingerprint. The system SHALL match the scanned fingerprint against stored templates.

#### Scenario: Identify patient by fingerprint

- GIVEN multiple patients have fingerprints registered
- WHEN a fingerprint is scanned at reception
- THEN the system matches against templates
- AND displays the matching patient
- AND logs the identification event

#### Scenario: No match found

- GIVEN a fingerprint is scanned
- AND no matching template exists
- THEN the system displays: "No se encontró paciente con esta huella"
- AND offers to register new patient or search manually

#### Scenario: Multiple matches

- GIVEN a fingerprint matches multiple templates (rare, low quality)
- WHEN the scan completes
- THEN the system displays potential matches
- AND allows user to select the correct patient

---

### Requirement: Practitioner Authentication by Fingerprint

The system SHOULD allow practitioners to authenticate using fingerprint. The system SHALL link fingerprints to user accounts.

#### Scenario: Practitioner logs in with fingerprint

- GIVEN a practitioner has registered fingerprint
- WHEN the practitioner scans fingerprint at login
- THEN the system authenticates the user
- AND creates a session
- AND logs the biometric authentication

#### Scenario: Fingerprint login fallback

- GIVEN fingerprint authentication fails
- WHEN the scan does not match
- THEN the system allows password login as fallback
- AND logs the failed biometric attempt

---

### Requirement: Biometric Device Integration

The system MUST integrate with standard biometric devices. The system SHOULD support Digital Persona and Suprema devices.

#### Scenario: Device auto-detection

- GIVEN a supported biometric device is connected
- WHEN the system starts
- THEN the device is detected
- AND displayed as available in settings

#### Scenario: Device not available

- GIVEN no biometric device is connected
- WHEN fingerprint capture is attempted
- THEN the system displays: "No se detectó dispositivo biométrico"
- AND hides biometric options

---

### Requirement: Biometric Data Security

The system MUST encrypt all biometric templates. The system SHALL use AES-256 encryption. The system MUST NOT store fingerprint images.

#### Scenario: Template encryption

- GIVEN a fingerprint template is captured
- WHEN the template is saved
- THEN it is encrypted using Laravel's encrypt()
- AND the encryption uses AES-256-GCM
- AND only the encrypted blob is stored

#### Scenario: Template decryption for matching

- GIVEN an encrypted template exists
- WHEN fingerprint matching is performed
- THEN the template is decrypted in memory
- AND compared with the scanned template
- AND decrypted data is never written to disk

---

### Requirement: Biometric Consent Management

The system MUST obtain informed consent before capturing biometric data. The system SHALL allow patients to withdraw consent.

#### Scenario: Consent recorded

- GIVEN a patient is registering biometrics
- WHEN consent is given
- THEN a consent record is created with:
  - patient_id
  - consent_type: "biometric"
  - consent_date
  - consent_text (what was agreed to)
- AND stored in consent_records table

#### Scenario: Withdraw consent

- GIVEN a patient has registered biometrics
- WHEN the patient requests deletion
- THEN the fingerprint template is deleted
- AND the consent record is updated to "withdrawn"
- AND the audit log records the deletion

---

### Requirement: Biometric Audit Trail

The system MUST log all biometric operations. The system SHALL include: capture, identification attempts, authentication, and deletion.

#### Scenario: Log fingerprint capture

- GIVEN a fingerprint is captured
- THEN an audit log entry is created with:
  - event: "biometric.capture"
  - patient_id: {patient_id}
  - user_id: {performing_user}
  - device_id: {device_serial}
  - timestamp: {iso_datetime}

#### Scenario: Log identification attempt

- GIVEN a fingerprint identification is attempted
- THEN an audit log entry is created with:
  - event: "biometric.identify"
  - result: "match" | "no_match"
  - patient_id: {matched_patient_id or null}
  - timestamp: {iso_datetime}

---

### Requirement: Fingerprint Quality Validation

The system SHOULD validate fingerprint quality during capture. The system SHALL reject low-quality scans.

#### Scenario: High quality scan accepted

- GIVEN a fingerprint is scanned
- WHEN quality score >= 70%
- THEN the scan is accepted
- AND template is extracted

#### Scenario: Low quality scan rejected

- GIVEN a fingerprint is scanned
- WHEN quality score < 70%
- THEN the scan is rejected
- AND displays: "Calidad de huella insuficiente. Intente nuevamente."
- AND offers guidance (clean sensor, adjust finger position)

---

### Requirement: Multiple Fingers Per Patient

The system SHOULD allow registering multiple fingers per patient. The system SHALL support up to 10 fingerprints per patient.

#### Scenario: Register multiple fingers

- GIVEN a patient has one finger registered
- WHEN additional finger is captured
- THEN the template is stored with finger identifier
- AND labeled (e.g., "Índice derecho", "Pulgar izquierdo")

#### Scenario: Use any registered finger for identification

- GIVEN a patient has 3 fingers registered
- WHEN any of the 3 fingers is scanned
- THEN the patient is identified
- AND the specific finger matched is logged

---

### Requirement: Biometric Settings Configuration

The system MUST allow administrators to configure biometric settings. The system SHALL support enabling/disabling biometric features.

#### Scenario: Enable biometric authentication

- GIVEN an admin accesses system settings
- WHEN "Autenticación biométrica" is enabled
- THEN fingerprint login option appears on login page
- AND practitioners can register fingerprints

#### Scenario: Disable biometric for patient identification

- GIVEN an admin disables patient biometric ID
- WHEN reception attempts fingerprint identification
- THEN the option is hidden
- AND only manual search is available