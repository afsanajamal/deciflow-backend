<?php

namespace App\Services;

use App\Models\Request;

class RuleEngineService
{
    /**
     * Determine approval steps based on request amount (MVP hardcoded rules)
     *
     * @return array Array of role names required for approval
     */
    public function determineApprovalSteps(Request $request): array
    {
        $amount = $request->amount;

        // MVP Rules based on amount
        if ($amount <= 100000) {
            return ['approver'];
        } elseif ($amount <= 500000) {
            return ['approver', 'dept_admin'];
        } else {
            return ['approver', 'dept_admin', 'super_admin'];
        }
    }

    /**
     * Validate request based on category constraints
     *
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateRequestByCategory(Request $request): array
    {
        $errors = [];

        // SOFTWARE requires vendor_name
        if ($request->category === 'SOFTWARE' && empty($request->vendor_name)) {
            $errors[] = 'vendor_name is required for SOFTWARE category';
        }

        // TRAVEL requires travel dates
        if ($request->category === 'TRAVEL') {
            if (empty($request->travel_start_date)) {
                $errors[] = 'travel_start_date is required for TRAVEL category';
            }
            if (empty($request->travel_end_date)) {
                $errors[] = 'travel_end_date is required for TRAVEL category';
            }
        }

        // URGENT requires urgency_reason
        if ($request->urgency === 'URGENT' && empty($request->urgency_reason)) {
            $errors[] = 'urgency_reason is required when urgency is URGENT';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
