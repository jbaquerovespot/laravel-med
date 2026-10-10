<?php

namespace App\Observers;

use App\Models\Encounter;

/**
 * Observer for Encounter model to log all CRUD operations.
 * Complies with Resolución 1995 de 1999 (audit trail requirements).
 */
class EncounterObserver
{
    /**
     * Handle the Encounter "created" event.
     */
    public function created(Encounter $encounter): void
    {
        activity()
            ->performedOn($encounter)
            ->withProperties([
                'patient_id' => $encounter->patient_id,
                'practitioner_id' => $encounter->practitioner_id,
                'status' => $encounter->status,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('encounter.created');
    }

    /**
     * Handle the Encounter "updated" event.
     */
    public function updated(Encounter $encounter): void
    {
        $changes = $encounter->getChanges();
        unset($changes['updated_at']);

        if (empty($changes)) {
            return;
        }

        activity()
            ->performedOn($encounter)
            ->withProperties([
                'patient_id' => $encounter->patient_id,
                'changes' => $changes,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('encounter.updated');
    }

    /**
     * Handle the Encounter "deleted" event.
     */
    public function deleted(Encounter $encounter): void
    {
        activity()
            ->performedOn($encounter)
            ->withProperties([
                'patient_id' => $encounter->patient_id,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('encounter.deleted');
    }

    /**
     * Handle the Encounter "restored" event.
     */
    public function restored(Encounter $encounter): void
    {
        activity()
            ->performedOn($encounter)
            ->withProperties([
                'patient_id' => $encounter->patient_id,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('encounter.restored');
    }
}
