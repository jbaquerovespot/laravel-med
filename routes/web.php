<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Logout;
use App\Livewire\Biometric\Enroll as BiometricEnroll;
use App\Livewire\Biometric\Identify as BiometricIdentify;
use App\Livewire\Conditions\Form as ConditionForm;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Encounters\Create as EncounterCreate;
use App\Livewire\Encounters\Show as EncounterShow;
use App\Livewire\Laboratory\Form as LaboratoryForm;
use App\Livewire\Medications\Form as MedicationForm;
use App\Livewire\Patients\Create;
use App\Livewire\Patients\Edit;
use App\Livewire\Patients\Index;
use App\Livewire\Patients\Show;
use App\Livewire\Rda\Generate as RdaGenerate;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

// Authenticated routes
Route::middleware(['auth', 'session.timeout'])->group(function () {
    // Logout
    Route::get('/logout', Logout::class)->name('logout');

    // Dashboard
    Route::get('/dashboard', DashboardIndex::class)->name('dashboard');

    // Patient management routes
    Route::prefix('patients')->name('patients.')->group(function () {
        Route::get('/', Index::class)->name('index')->middleware('can:patients.view');
        Route::get('/create', Create::class)->name('create')->middleware('can:patients.create');
        Route::get('/{id}', Show::class)->name('show')->middleware('can:patients.view');
        Route::get('/{id}/edit', Edit::class)->name('edit')->middleware('can:patients.edit');
    });

    // Encounter routes
    Route::prefix('encounters')->name('encounters.')->group(function () {
        Route::get('/create', EncounterCreate::class)->name('create')->middleware('can:encounters.create');
        Route::get('/create/{patient}', EncounterCreate::class)->name('create-for-patient')->middleware('can:encounters.create');
        Route::get('/{id}', EncounterShow::class)->name('show')->middleware('can:patients.view');
    });

    // RDA generation routes
    Route::get('/rda/{encounter}', RdaGenerate::class)->name('rda.generate')->middleware('can:rda.generate');

    // Conditions routes
    Route::prefix('conditions')->name('conditions.')->group(function () {
        Route::get('/create/{patient?}', ConditionForm::class)->name('create')->middleware('can:conditions.create');
    });

    // Medications routes
    Route::prefix('medications')->name('medications.')->group(function () {
        Route::get('/prescribe/{patient?}', MedicationForm::class)->name('prescribe')->middleware('can:medications.prescribe');
    });

    // Laboratory routes
    Route::prefix('laboratory')->name('laboratory.')->group(function () {
        Route::get('/order/{patient?}', LaboratoryForm::class)->name('order')->middleware('can:laboratory.order');
    });

    // Biometric routes
    Route::prefix('biometric')->name('biometric.')->group(function () {
        Route::get('/enroll/{patient?}', BiometricEnroll::class)->name('enroll')->middleware('can:patients.edit');
        Route::get('/identify', BiometricIdentify::class)->name('identify')->middleware('can:patients.view');
    });
});
