<?php

namespace App\Observers;

use App\Models\Condition;

/**
 * Observer for Condition model to log all CRUD operations.
 * Complies with Resolución 1995 de 1999 (audit trail requirements).
 */
class ConditionObserver
{
    /**
     * Handle the Condition "created" event.
     */
    public function created(Condition $condition): void
    {
        activity()
            ->performedOn($condition)
            ->withProperties([
                'patient_id' => $condition->patient_id,
                'encounter_id' => $condition->encounter_id,
                'code' => $condition->code,
                'display' => $condition->display,
                'clinical_status' => $condition->clinical_status,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('condition.created');
    }

    /**
     * Handle the Condition "updated" event.
     */
    public function updated(Condition $condition): void
    {
        $changes = $condition->getChanges();
        unset($changes['updated_at']);

        if (empty($changes)) {
            return;
        }

        activity()
            ->performedOn($condition)
            ->withProperties([
                'patient_id' => $condition->patient_id,
                'changes' => $changes,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('condition.updated');
    }

    /**
     * Handle the Condition "deleted" event.
     */
    public function deleted(Condition $condition): void
    {
        activity()
            ->performedOn($condition)
            ->withProperties([
                'patient_id' => $condition->patient_id,
                'code' => $condition->code,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('condition.deleted');
    }
}
