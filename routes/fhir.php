<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Fhir\FhirPatientController;
use App\Http\Controllers\Fhir\FhirEncounterController;
use App\Http\Controllers\Fhir\FhirObservationController;
use App\Http\Controllers\Fhir\FhirConditionController;
use App\Http\Controllers\Fhir\FhirMedicationRequestController;
use App\Http\Controllers\Fhir\FhirServiceRequestController;
use App\Http\Controllers\Fhir\FhirAllergyIntoleranceController;
use App\Http\Controllers\Fhir\FhirPractitionerController;
use App\Http\Controllers\Fhir\FhirBundleController;

/*
|--------------------------------------------------------------------------
| FHIR API Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/fhir and protected by FHIR middleware
| (auth, audit, content negotiation, rate limiting).
|
*/

// Patient endpoints
Route::get('/Patient', [FhirPatientController::class, 'search']);
Route::get('/Patient/{id}', [FhirPatientController::class, 'show']);
Route::post('/Patient', [FhirPatientController::class, 'store']);
Route::put('/Patient/{id}', [FhirPatientController::class, 'update']);
Route::delete('/Patient/{id}', [FhirPatientController::class, 'destroy']);

// Encounter endpoints
Route::get('/Encounter', [FhirEncounterController::class, 'search']);
Route::get('/Encounter/{id}', [FhirEncounterController::class, 'show']);
Route::post('/Encounter', [FhirEncounterController::class, 'store']);
Route::put('/Encounter/{id}', [FhirEncounterController::class, 'update']);

// Observation endpoints
Route::get('/Observation', [FhirObservationController::class, 'search']);
Route::get('/Observation/{id}', [FhirObservationController::class, 'show']);
Route::post('/Observation', [FhirObservationController::class, 'store']);

// Condition endpoints
Route::get('/Condition', [FhirConditionController::class, 'search']);
Route::get('/Condition/{id}', [FhirConditionController::class, 'show']);
Route::post('/Condition', [FhirConditionController::class, 'store']);

// MedicationRequest endpoints
Route::get('/MedicationRequest', [FhirMedicationRequestController::class, 'search']);
Route::get('/MedicationRequest/{id}', [FhirMedicationRequestController::class, 'show']);
Route::post('/MedicationRequest', [FhirMedicationRequestController::class, 'store']);

// ServiceRequest endpoints
Route::get('/ServiceRequest', [FhirServiceRequestController::class, 'search']);
Route::get('/ServiceRequest/{id}', [FhirServiceRequestController::class, 'show']);
Route::post('/ServiceRequest', [FhirServiceRequestController::class, 'store']);

// AllergyIntolerance endpoints
Route::get('/AllergyIntolerance', [FhirAllergyIntoleranceController::class, 'search']);
Route::get('/AllergyIntolerance/{id}', [FhirAllergyIntoleranceController::class, 'show']);
Route::post('/AllergyIntolerance', [FhirAllergyIntoleranceController::class, 'store']);

// Practitioner endpoints
Route::get('/Practitioner', [FhirPractitionerController::class, 'search']);
Route::get('/Practitioner/{id}', [FhirPractitionerController::class, 'show']);
Route::post('/Practitioner', [FhirPractitionerController::class, 'store']);

// Bundle endpoints
Route::post('/Bundle', [FhirBundleController::class, 'transaction']);
Route::get('/$rda', [FhirBundleController::class, 'generateRda']);
Route::get('/metadata', [FhirBundleController::class, 'capabilityStatement']);
