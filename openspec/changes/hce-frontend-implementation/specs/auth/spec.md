# Authentication Specification

## Purpose

Authentication system for Vespot HCE providing secure access control with login/password credentials, session management, role-based access control (RBAC), and audit logging. Complies with Resolución 1995 de 1999 requirements for controlled access to clinical records.

## Requirements

### Requirement: User Login with Credentials

The system MUST authenticate users with email and password credentials. The system SHALL validate credentials against stored user records and establish a session upon successful authentication.

#### Scenario: Successful login with valid credentials

- GIVEN a user exists with email "doctor@vespot.co" and password "SecurePass123!"
- WHEN the user submits the login form with correct credentials
- THEN the system authenticates the user
- AND creates a new session
- AND logs the authentication event with timestamp, IP, and user agent
- AND redirects to the dashboard

#### Scenario: Failed login with invalid password

- GIVEN a user exists with email "doctor@vespot.co"
- WHEN the user submits the login form with incorrect password
- THEN the system rejects the authentication
- AND displays "Las credenciales no son válidas" error message
- AND logs the failed attempt with email and IP address
- AND increments the failed attempts counter

#### Scenario: Failed login with non-existent email

- GIVEN no user exists with email "nonexistent@vespot.co"
- WHEN the user submits the login form
- THEN the system rejects the authentication
- AND displays a generic error message (not revealing email existence)

---

### Requirement: Rate Limiting on Failed Attempts

The system MUST limit login attempts to prevent brute force attacks. After 5 failed attempts, the account SHALL be locked for 30 minutes.

#### Scenario: Account locked after 5 failed attempts

- GIVEN a user has failed login 4 times
- WHEN the user fails login a 5th time
- THEN the account is locked for 30 minutes
- AND displays "Cuenta bloqueada. Intente nuevamente en 30 minutos."
- AND logs the lockout event

#### Scenario: Locked account rejects login attempts

- GIVEN an account is locked due to failed attempts
- WHEN the user attempts to login with correct credentials
- THEN the system rejects the login
- AND displays the remaining lockout time

#### Scenario: Lockout expires after 30 minutes

- GIVEN an account was locked 30 minutes ago
- WHEN the user attempts to login with correct credentials
- THEN the system authenticates the user
- AND resets the failed attempts counter

---

### Requirement: Session Timeout

The system MUST automatically terminate sessions after 15 minutes of inactivity. The system SHOULD allow users to explicitly log out.

#### Scenario: Session expires after inactivity

- GIVEN a user is logged in
- WHEN the user is inactive for 15 minutes
- THEN the session is terminated
- AND the user is redirected to the login page
- AND displays "Su sesión ha expirado por inactividad."

#### Scenario: User explicitly logs out

- GIVEN a user is logged in
- WHEN the user clicks "Cerrar Sesión"
- THEN the session is terminated
- AND the user is redirected to the login page
- AND logs the logout event with timestamp

---

### Requirement: Password Strength Requirements

The system MUST enforce password strength requirements. Passwords SHALL be at least 8 characters and contain at least one uppercase letter, one number, and one special character.

#### Scenario: Password validation on registration

- GIVEN a new user is registering
- WHEN the user enters password "weak"
- THEN the system rejects the password
- AND displays "La contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un carácter especial."

#### Scenario: Strong password accepted

- GIVEN a new user is registering
- WHEN the user enters password "SecurePass123!"
- THEN the system accepts the password
- AND hashes the password with bcrypt or argon2id

---

### Requirement: Role-Based Access Control

The system MUST implement RBAC with predefined roles. The system SHALL support at minimum: admin, doctor, nurse, and receptionist roles.

#### Scenario: Doctor accesses patient records

- GIVEN a user has role "doctor"
- WHEN the user navigates to patient records
- THEN the system grants access
- AND displays the patient list

#### Scenario: Receptionist denied access to clinical notes

- GIVEN a user has role "receptionist"
- WHEN the user attempts to access clinical notes
- THEN the system denies access
- AND displays "No tiene permisos para acceder a esta sección."

#### Scenario: Admin manages user roles

- GIVEN a user has role "admin"
- WHEN the user accesses the user management section
- THEN the system grants access
- AND displays the user management interface

---

### Requirement: Two-Factor Authentication (Optional)

The system SHOULD support two-factor authentication (2FA) for admin users. The system MAY use TOTP (Time-based One-Time Password) for 2FA.

#### Scenario: Admin enables 2FA

- GIVEN a user has role "admin"
- WHEN the user enables 2FA in settings
- THEN the system generates a TOTP secret
- AND displays a QR code for authenticator app setup

#### Scenario: Login with 2FA enabled

- GIVEN a user has 2FA enabled
- WHEN the user logs in with correct credentials
- THEN the system prompts for 2FA code
- AND validates the TOTP code
- AND authenticates the user upon valid code

---

### Requirement: Audit Logging for Authentication Events

The system MUST log all authentication events including login, logout, failed attempts, and lockouts. Logs SHALL include timestamp, user ID (if applicable), IP address, and user agent.

#### Scenario: Login event logged

- GIVEN a user successfully logs in
- THEN an audit log entry is created with:
  - event: "user.login"
  - user_id: {user_id}
  - ip_address: {client_ip}
  - user_agent: {browser_string}
  - timestamp: {iso_datetime}

#### Scenario: Failed attempt logged

- GIVEN a user fails to log in
- THEN an audit log entry is created with:
  - event: "user.login_failed"
  - email: {attempted_email}
  - ip_address: {client_ip}
  - timestamp: {iso_datetime}