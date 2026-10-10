<?php

namespace App\Observers;

use App\Models\Patient;

/**
 * Observer for Patient model to log all CRUD operations.
 * Complies with Resolución 1995 de 1999 (audit trail requirements).
 */
class PatientObserver
{
    /**
     * Handle the Patient "created" event.
     */
    public function created(Patient $patient): void
    {
        activity()
            ->performedOn($patient)
            ->withProperties([
                'document_number' => $patient->document_number,
                'full_name' => $patient->full_name,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('patient.created');
    }

    /**
     * Handle the Patient "updated" event.
     */
    public function updated(Patient $patient): void
    {
        $changes = $patient->getChanges();
        unset($changes['updated_at']); // Don't log timestamp changes

        if (empty($changes)) {
            return;
        }

        activity()
            ->performedOn($patient)
            ->withProperties([
                'document_number' => $patient->document_number,
                'changes' => $changes,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('patient.updated');
    }

    /**
     * Handle the Patient "deleted" event.
     */
    public function deleted(Patient $patient): void
    {
        activity()
            ->performedOn($patient)
            ->withProperties([
                'document_number' => $patient->document_number,
                'full_name' => $patient->full_name,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('patient.deleted');
    }

    /**
     * Handle the Patient "restored" event.
     */
    public function restored(Patient $patient): void
    {
        activity()
            ->performedOn($patient)
            ->withProperties([
                'document_number' => $patient->document_number,
                'full_name' => $patient->full_name,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('patient.restored');
    }

    /**
     * Handle the Patient "force deleted" event.
     */
    public function forceDeleted(Patient $patient): void
    {
        activity()
            ->performedOn($patient)
            ->withProperties([
                'document_number' => $patient->document_number,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('patient.force_deleted');
    }
}
