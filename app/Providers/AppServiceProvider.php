<?php

namespace App\Providers;

use App\Models\Condition;
use App\Models\Encounter;
use App\Models\MedicationRequest;
use App\Models\Patient;
use App\Observers\ConditionObserver;
use App\Observers\EncounterObserver;
use App\Observers\MedicationRequestObserver;
use App\Observers\PatientObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers for audit logging
        Patient::observe(PatientObserver::class);
        Encounter::observe(EncounterObserver::class);
        Condition::observe(ConditionObserver::class);
        MedicationRequest::observe(MedicationRequestObserver::class);
    }
}
