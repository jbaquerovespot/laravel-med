<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// FHIR API Routes
Route::prefix('fhir')->middleware('fhir')->group(base_path('routes/fhir.php'));
