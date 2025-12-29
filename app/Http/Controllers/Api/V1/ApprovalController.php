<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveRequestRequest;
use App\Http\Requests\RejectRequestRequest;
use App\Http\Requests\ReturnRequestRequest;
use App\Models\Request as RequestModel;
use App\Services\ApprovalService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private ApprovalService $approvalService
    ) {}

    /**
     * Get pending approvals inbox for current user
     *
     * @OA\Get(
     *     path="/api/v1/approvals/inbox",
     *     tags={"Approvals"},
     *     summary="Get approval inbox",
     *     description="Get all requests pending approval for the current user's role",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of pending approvals",
     *
     *         @OA\JsonContent(type="array", @OA\Items(type="object"))
     *     )
     * )
     */
    public function inbox(Request $request)
    {
        $user = $request->user();
        $userRole = $user->role->name;

        // Get requests with pending approval steps matching user's role
        $requests = RequestModel::whereHas('approvalSteps', function ($query) use ($userRole) {
            $query->where('approver_role', $userRole)
                ->where('status', 'pending');
        })
            ->with(['user', 'department', 'approvalSteps' => function ($query) use ($userRole) {
                $query->where('approver_role', $userRole)->where('status', 'pending');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }

    /**
     * Approve a request
     *
     * @OA\Post(
     *     path="/api/v1/requests/{id}/approve",
     *     tags={"Approvals"},
     *     summary="Approve request",
     *     description="Approve a request at current approval step",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Request ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="comment", type="string", example="Approved - looks good")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Request approved successfully",
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=422, description="Approval failed"),
     *     @OA\Response(response=404, description="Request not found")
     * )
     */
    public function approve(ApproveRequestRequest $request, $id)
    {
        $requestModel = RequestModel::findOrFail($id);

        try {
            $this->approvalService->approve($requestModel, $request->user(), $request->comment);

            return response()->json($requestModel->fresh()->load(['approvalSteps', 'auditLogs']));
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'APPROVE_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }
    }

    /**
     * Reject a request
     *
     * @OA\Post(
     *     path="/api/v1/requests/{id}/reject",
     *     tags={"Approvals"},
     *     summary="Reject request",
     *     description="Reject a request at current approval step",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Request ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"comment"},
     *
     *             @OA\Property(property="comment", type="string", example="Rejected - insufficient justification")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Request rejected successfully",
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=422, description="Rejection failed"),
     *     @OA\Response(response=404, description="Request not found")
     * )
     */
    public function reject(RejectRequestRequest $request, $id)
    {
        $requestModel = RequestModel::findOrFail($id);

        try {
            $this->approvalService->reject($requestModel, $request->user(), $request->comment);

            return response()->json($requestModel->fresh()->load(['approvalSteps', 'auditLogs']));
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'REJECT_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }
    }

    /**
     * Return a request to requester
     *
     * @OA\Post(
     *     path="/api/v1/requests/{id}/return",
     *     tags={"Approvals"},
     *     summary="Return request",
     *     description="Return a request to requester for revision",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Request ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"comment"},
     *
     *             @OA\Property(property="comment", type="string", example="Please provide more details about the vendor")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Request returned successfully",
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=422, description="Return failed"),
     *     @OA\Response(response=404, description="Request not found")
     * )
     */
    public function return(ReturnRequestRequest $request, $id)
    {
        $requestModel = RequestModel::findOrFail($id);

        try {
            $this->approvalService->returnRequest($requestModel, $request->user(), $request->comment);

            return response()->json($requestModel->fresh()->load(['approvalSteps', 'auditLogs']));
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'RETURN_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }
    }
}
