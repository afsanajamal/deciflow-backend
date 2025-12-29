<?php

namespace App\Services;

use App\Models\ApprovalStep;
use App\Models\Request;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ApprovalRequestedNotification;
use App\Notifications\RequestSubmittedNotification;
use Illuminate\Support\Facades\Notification;

class ApprovalService
{
    public function __construct(
        private RuleEngineService $ruleEngine,
        private StateMachineService $stateMachine
    ) {}

    /**
     * Submit a request and generate approval steps
     */
    public function submitRequest(Request $request, User $user): void
    {
        // Validate request is in DRAFT status
        if ($request->status !== 'DRAFT') {
            throw new \Exception('Only DRAFT requests can be submitted');
        }

        // Validate category-specific requirements
        $validation = $this->ruleEngine->validateRequestByCategory($request);
        if (! $validation['valid']) {
            throw new \Exception('Validation failed: '.implode(', ', $validation['errors']));
        }

        // Determine approval steps based on amount
        $approvalRoles = $this->ruleEngine->determineApprovalSteps($request);

        // Create approval steps
        foreach ($approvalRoles as $index => $role) {
            ApprovalStep::create([
                'request_id' => $request->id,
                'step_number' => $index + 1,
                'approver_role' => $role,
                'status' => 'pending',
            ]);
        }

        // Transition to SUBMITTED then IN_REVIEW
        $this->stateMachine->transition($request, 'SUBMITTED', $user, ['submitted_at' => now()]);
        $this->stateMachine->transition($request, 'IN_REVIEW', $user);

        // Notify requester that request was submitted
        $request->user->notify(new RequestSubmittedNotification($request));

        // Notify first approvers that they have a new request to review
        $this->notifyApprovers($request, 1);
    }

    /**
     * Get the current pending approval step for a request
     */
    public function getCurrentPendingStep(Request $request): ?ApprovalStep
    {
        return $request->approvalSteps()
            ->where('status', 'pending')
            ->orderBy('step_number')
            ->first();
    }

    /**
     * Approve a request at the current step
     */
    public function approve(Request $request, User $user, ?string $comment = null): void
    {
        // Validate request is in review
        if ($request->status !== 'IN_REVIEW') {
            throw new \Exception('Request must be IN_REVIEW to approve');
        }

        // Get current pending step
        $currentStep = $this->getCurrentPendingStep($request);
        if (! $currentStep) {
            throw new \Exception('No pending approval step found');
        }

        // Verify user has correct role
        if ($user->role->name !== $currentStep->approver_role) {
            throw new \Exception('User does not have permission to approve this step');
        }

        // Update approval step
        $currentStep->update([
            'approver_id' => $user->id,
            'status' => 'approved',
            'comment' => $comment,
            'approved_at' => now(),
        ]);

        // Log the approval
        $this->stateMachine->logTransition(
            $request,
            $user,
            'IN_REVIEW',
            'IN_REVIEW',
            ['step_approved' => $currentStep->step_number, 'comment' => $comment]
        );

        // Check if all steps are approved
        $remainingSteps = $request->approvalSteps()->where('status', 'pending')->count();
        if ($remainingSteps === 0) {
            // All steps approved, move to APPROVED
            $this->stateMachine->transition($request, 'APPROVED', $user);

            // Notify requester that request is fully approved
            $request->user->notify(new \App\Notifications\RequestApprovedNotification($request));
        } else {
            // Notify next approvers
            $nextStep = $this->getCurrentPendingStep($request);
            if ($nextStep) {
                $this->notifyApprovers($request, $nextStep->step_number);
            }
        }
    }

    /**
     * Reject a request at the current step
     */
    public function reject(Request $request, User $user, ?string $comment = null): void
    {
        if ($request->status !== 'IN_REVIEW') {
            throw new \Exception('Request must be IN_REVIEW to reject');
        }

        $currentStep = $this->getCurrentPendingStep($request);
        if (! $currentStep) {
            throw new \Exception('No pending approval step found');
        }

        if ($user->role->name !== $currentStep->approver_role) {
            throw new \Exception('User does not have permission to reject this step');
        }

        // Update approval step
        $currentStep->update([
            'approver_id' => $user->id,
            'status' => 'rejected',
            'comment' => $comment,
            'approved_at' => now(),
        ]);

        // Transition to REJECTED
        $this->stateMachine->transition($request, 'REJECTED', $user, ['comment' => $comment]);

        // Notify requester that request was rejected
        $request->user->notify(new \App\Notifications\RequestRejectedNotification($request, $comment));
    }

    /**
     * Return a request to the requester for modifications
     */
    public function returnRequest(Request $request, User $user, ?string $comment = null): void
    {
        if ($request->status !== 'IN_REVIEW') {
            throw new \Exception('Request must be IN_REVIEW to return');
        }

        $currentStep = $this->getCurrentPendingStep($request);
        if (! $currentStep) {
            throw new \Exception('No pending approval step found');
        }

        if ($user->role->name !== $currentStep->approver_role) {
            throw new \Exception('User does not have permission to return this step');
        }

        // Update approval step
        $currentStep->update([
            'approver_id' => $user->id,
            'status' => 'returned',
            'comment' => $comment,
            'approved_at' => now(),
        ]);

        // Transition to RETURNED
        $this->stateMachine->transition($request, 'RETURNED', $user, ['comment' => $comment]);

        // Notify requester that request was returned
        $request->user->notify(new \App\Notifications\RequestReturnedNotification($request, $comment));
    }

    /**
     * Notify all users with the required role for a given approval step
     */
    private function notifyApprovers(Request $request, int $stepNumber): void
    {
        $step = $request->approvalSteps()
            ->where('step_number', $stepNumber)
            ->where('status', 'pending')
            ->first();

        if (! $step) {
            return;
        }

        // Get all users with the required role
        $role = Role::where('name', $step->approver_role)->first();
        if (! $role) {
            return;
        }

        $approvers = $role->users()->get();

        // Send notification to all approvers
        Notification::send($approvers, new ApprovalRequestedNotification($request, $stepNumber));
    }
}
