<?php

namespace App\Observers;

use App\Models\MedicationRequest;

/**
 * Observer for MedicationRequest model to log all CRUD operations.
 * Complies with Resolución 1995 de 1999 (audit trail requirements).
 */
class MedicationRequestObserver
{
    /**
     * Handle the MedicationRequest "created" event.
     */
    public function created(MedicationRequest $medicationRequest): void
    {
        activity()
            ->performedOn($medicationRequest)
            ->withProperties([
                'patient_id' => $medicationRequest->patient_id,
                'encounter_id' => $medicationRequest->encounter_id,
                'medication_display' => $medicationRequest->medication_display,
                'status' => $medicationRequest->status,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('medication_request.created');
    }

    /**
     * Handle the MedicationRequest "updated" event.
     */
    public function updated(MedicationRequest $medicationRequest): void
    {
        $changes = $medicationRequest->getChanges();
        unset($changes['updated_at']);

        if (empty($changes)) {
            return;
        }

        activity()
            ->performedOn($medicationRequest)
            ->withProperties([
                'patient_id' => $medicationRequest->patient_id,
                'changes' => $changes,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('medication_request.updated');
    }

    /**
     * Handle the MedicationRequest "deleted" event.
     */
    public function deleted(MedicationRequest $medicationRequest): void
    {
        activity()
            ->performedOn($medicationRequest)
            ->withProperties([
                'patient_id' => $medicationRequest->patient_id,
                'medication_display' => $medicationRequest->medication_display,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('medication_request.deleted');
    }
}
