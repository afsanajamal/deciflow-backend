<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequestRequest;
use App\Http\Requests\UpdateRequestRequest;
use App\Models\Request as RequestModel;
use App\Services\ApprovalService;
use App\Services\StateMachineService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private ApprovalService $approvalService,
        private StateMachineService $stateMachine
    ) {}

    /**
     * List requests with filters
     *
     * @OA\Get(
     *     path="/api/v1/requests",
     *     tags={"Requests"},
     *     summary="List all requests",
     *     description="Get paginated list of requests with optional filters",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *
     *         @OA\Schema(type="string", enum={"DRAFT", "SUBMITTED", "IN_REVIEW", "RETURNED", "APPROVED", "REJECTED", "CANCELLED", "ARCHIVED"})
     *     ),
     *
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by category",
     *
     *         @OA\Schema(type="string", enum={"EQUIPMENT", "SOFTWARE", "SERVICE", "TRAVEL"})
     *     ),
     *
     *     @OA\Parameter(name="department_id", in="query", description="Filter by department ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="amount_min", in="query", description="Minimum amount", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="amount_max", in="query", description="Maximum amount", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of requests",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = RequestModel::with(['user', 'department', 'approvalSteps']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->has('amount_min')) {
            $query->where('amount', '>=', $request->amount_min);
        }
        if ($request->has('amount_max')) {
            $query->where('amount', '<=', $request->amount_max);
        }
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by user role
        $user = $request->user();
        if ($user->role->name === 'requester') {
            $query->where('user_id', $user->id);
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($requests);
    }

    /**
     * Create a new request (draft)
     *
     * @OA\Post(
     *     path="/api/v1/requests",
     *     tags={"Requests"},
     *     summary="Create new request",
     *     description="Create a new purchase request in DRAFT status",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"title","description","category","amount"},
     *
     *             @OA\Property(property="title", type="string", example="New laptop for development"),
     *             @OA\Property(property="description", type="string", example="MacBook Pro 16-inch for development work"),
     *             @OA\Property(property="category", type="string", enum={"EQUIPMENT", "SOFTWARE", "SERVICE", "TRAVEL"}, example="EQUIPMENT"),
     *             @OA\Property(property="amount", type="integer", example=300000, description="Amount in JPY"),
     *             @OA\Property(property="vendor_name", type="string", example="Apple Inc."),
     *             @OA\Property(property="urgency", type="string", enum={"NORMAL", "URGENT"}, example="NORMAL")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Request created successfully",
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreRequestRequest $request)
    {
        $validated = $request->validated();

        $validated['user_id'] = $request->user()->id;
        $validated['department_id'] = $request->user()->department_id;
        $validated['status'] = 'DRAFT';

        $requestModel = RequestModel::create($validated);

        // Log creation
        $this->stateMachine->logTransition($requestModel, $request->user(), null, 'DRAFT', []);

        return response()->json($requestModel->load(['user', 'department']), 201);
    }

    /**
     * Get a single request
     *
     * @OA\Get(
     *     path="/api/v1/requests/{id}",
     *     tags={"Requests"},
     *     summary="Get request details",
     *     description="Get detailed information about a specific request",
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
     *     @OA\Response(
     *         response=200,
     *         description="Request details",
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=404, description="Request not found"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function show(Request $request, $id)
    {
        $requestModel = RequestModel::with(['user', 'department', 'approvalSteps.approver', 'auditLogs.user'])
            ->findOrFail($id);

        // Check authorization
        $this->authorize('view', $requestModel);

        return response()->json($requestModel);
    }

    /**
     * Update a request (draft only)
     *
     * @OA\Put(
     *     path="/api/v1/requests/{id}",
     *     tags={"Requests"},
     *     summary="Update request",
     *     description="Update a request in DRAFT status",
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
     *
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="category", type="string", enum={"EQUIPMENT", "SOFTWARE", "SERVICE", "TRAVEL"}),
     *             @OA\Property(property="amount", type="integer"),
     *             @OA\Property(property="vendor_name", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Request updated successfully",
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=422, description="Cannot update non-DRAFT request"),
     *     @OA\Response(response=404, description="Request not found")
     * )
     */
    public function update(UpdateRequestRequest $request, $id)
    {
        $requestModel = RequestModel::findOrFail($id);

        // Check authorization
        $this->authorize('update', $requestModel);

        if ($requestModel->status !== 'DRAFT') {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_STATUS',
                    'message' => 'Only DRAFT requests can be updated',
                ],
            ], 422);
        }

        $validated = $request->validated();

        $requestModel->update($validated);

        return response()->json($requestModel->load(['user', 'department']));
    }

    /**
     * Submit a request
     *
     * @OA\Post(
     *     path="/api/v1/requests/{id}/submit",
     *     tags={"Requests"},
     *     summary="Submit request",
     *     description="Submit a request for approval",
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
     *     @OA\Response(
     *         response=200,
     *         description="Request submitted successfully",
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=422, description="Submit failed"),
     *     @OA\Response(response=404, description="Request not found")
     * )
     */
    public function submit(Request $request, $id)
    {
        $requestModel = RequestModel::findOrFail($id);

        // Check authorization
        $this->authorize('submit', $requestModel);

        try {
            $this->approvalService->submitRequest($requestModel, $request->user());

            return response()->json($requestModel->fresh()->load(['user', 'department', 'approvalSteps']));
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'SUBMIT_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }
    }

    /**
     * Cancel a request
     *
     * @OA\Post(
     *     path="/api/v1/requests/{id}/cancel",
     *     tags={"Requests"},
     *     summary="Cancel request",
     *     description="Cancel a submitted request",
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
     *     @OA\Response(
     *         response=200,
     *         description="Request cancelled successfully",
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=422, description="Cancel failed"),
     *     @OA\Response(response=404, description="Request not found")
     * )
     */
    public function cancel(Request $request, $id)
    {
        $requestModel = RequestModel::findOrFail($id);

        // Check authorization
        $this->authorize('cancel', $requestModel);

        try {
            $this->stateMachine->transition($requestModel, 'CANCELLED', $request->user());

            return response()->json($requestModel->fresh());
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'CANCEL_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }
    }

    /**
     * Archive a request (admin only)
     *
     * @OA\Post(
     *     path="/api/v1/requests/{id}/archive",
     *     tags={"Requests"},
     *     summary="Archive request",
     *     description="Archive a request (admin only)",
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
     *     @OA\Response(
     *         response=200,
     *         description="Request archived successfully",
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=403, description="Unauthorized - admin only"),
     *     @OA\Response(response=422, description="Archive failed"),
     *     @OA\Response(response=404, description="Request not found")
     * )
     */
    public function archive(Request $request, $id)
    {
        $requestModel = RequestModel::findOrFail($id);

        // Check authorization (admin only)
        if (! in_array($request->user()->role->name, ['dept_admin', 'super_admin'])) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only admins can archive requests',
                ],
            ], 403);
        }

        try {
            $this->stateMachine->transition($requestModel, 'ARCHIVED', $request->user());

            return response()->json($requestModel->fresh());
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'ARCHIVE_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }
    }
}
