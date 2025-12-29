<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Request;
use App\Models\User;

class StateMachineService
{
    /**
     * Valid state transitions
     */
    private const TRANSITIONS = [
        'DRAFT' => ['SUBMITTED', 'CANCELLED'],
        'SUBMITTED' => ['IN_REVIEW', 'CANCELLED'],
        'IN_REVIEW' => ['APPROVED', 'REJECTED', 'RETURNED', 'CANCELLED'],
        'RETURNED' => ['SUBMITTED', 'CANCELLED'],
        'APPROVED' => ['ARCHIVED'],
        'REJECTED' => ['ARCHIVED'],
        'CANCELLED' => ['ARCHIVED'],
        'ARCHIVED' => [],
    ];

    /**
     * Check if a transition is valid
     */
    public function canTransition(Request $request, string $toStatus, User $user): bool
    {
        $fromStatus = $request->status;

        // Check if transition is allowed
        if (! in_array($toStatus, self::TRANSITIONS[$fromStatus] ?? [])) {
            return false;
        }

        // Add role-based checks here if needed
        // For example, only super_admin can archive

        return true;
    }

    /**
     * Perform a state transition and log it
     *
     * @param  array  $meta  Additional metadata
     */
    public function transition(Request $request, string $toStatus, User $user, array $meta = []): void
    {
        $fromStatus = $request->status;

        if (! $this->canTransition($request, $toStatus, $user)) {
            throw new \Exception("Invalid transition from {$fromStatus} to {$toStatus}");
        }

        // Update request status
        $request->status = $toStatus;
        $request->save();

        // Log the transition
        $this->logTransition($request, $user, $fromStatus, $toStatus, $meta);
    }

    /**
     * Create an audit log entry for a transition
     */
    public function logTransition(Request $request, User $user, ?string $fromStatus, string $toStatus, array $meta = []): void
    {
        AuditLog::create([
            'request_id' => $request->id,
            'user_id' => $user->id,
            'action' => $this->getActionName($fromStatus, $toStatus),
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'meta' => $meta,
        ]);
    }

    /**
     * Get a human-readable action name from status transition
     */
    private function getActionName(?string $fromStatus, string $toStatus): string
    {
        if ($fromStatus === null) {
            return 'created';
        }

        return match ($toStatus) {
            'SUBMITTED' => 'submitted',
            'IN_REVIEW' => 'moved_to_review',
            'APPROVED' => 'approved',
            'REJECTED' => 'rejected',
            'RETURNED' => 'returned',
            'CANCELLED' => 'cancelled',
            'ARCHIVED' => 'archived',
            default => 'updated',
        };
    }
}
