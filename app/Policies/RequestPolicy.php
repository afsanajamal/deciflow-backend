<?php

namespace App\Policies;

use App\Models\Request;
use App\Models\User;

class RequestPolicy
{
    /**
     * Determine if the user can view the request
     */
    public function view(User $user, Request $request): bool
    {
        // Admins can view all
        if (in_array($user->role->name, ['super_admin', 'dept_admin'])) {
            return true;
        }

        // Owner can view their own
        if ($request->user_id === $user->id) {
            return true;
        }

        // Approvers can view if they have a pending step
        $hasPendingStep = $request->approvalSteps()
            ->where('approver_role', $user->role->name)
            ->where('status', 'pending')
            ->exists();

        return $hasPendingStep;
    }

    /**
     * Determine if the user can update the request
     */
    public function update(User $user, Request $request): bool
    {
        // Only owner can update
        if ($request->user_id !== $user->id) {
            return false;
        }

        // Only DRAFT requests can be updated
        return $request->status === 'DRAFT';
    }

    /**
     * Determine if the user can delete the request
     */
    public function delete(User $user, Request $request): bool
    {
        // Only owner can delete
        if ($request->user_id !== $user->id) {
            return false;
        }

        // Only DRAFT requests can be deleted
        return $request->status === 'DRAFT';
    }

    /**
     * Determine if the user can submit the request
     */
    public function submit(User $user, Request $request): bool
    {
        // Only owner can submit
        return $request->user_id === $user->id && $request->status === 'DRAFT';
    }

    /**
     * Determine if the user can cancel the request
     */
    public function cancel(User $user, Request $request): bool
    {
        // Owner can cancel before final decision
        if ($request->user_id === $user->id) {
            return ! in_array($request->status, ['APPROVED', 'REJECTED', 'ARCHIVED']);
        }

        return false;
    }
}
