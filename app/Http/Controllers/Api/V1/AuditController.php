<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    /**
     * Get all audit logs (super_admin only)
     *
     * @OA\Get(
     *     path="/api/v1/audit",
     *     tags={"Audit"},
     *     summary="List all audit logs",
     *     description="Get all audit logs across all requests (super admin only)",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of audit logs",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *
     *     @OA\Response(response=403, description="Unauthorized - super admin only")
     * )
     */
    public function index(Request $request)
    {
        // Check if user is super_admin
        if ($request->user()->role->name !== 'super_admin') {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only super admins can view all audit logs',
                ],
            ], 403);
        }

        $logs = AuditLog::with(['request', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($logs);
    }

    /**
     * Get audit logs for a specific request
     *
     * @OA\Get(
     *     path="/api/v1/requests/{id}/audit",
     *     tags={"Audit"},
     *     summary="Get request audit trail",
     *     description="Get all audit logs for a specific request",
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
     *         description="List of audit logs for the request",
     *
     *         @OA\JsonContent(type="array", @OA\Items(type="object"))
     *     ),
     *
     *     @OA\Response(response=404, description="Request not found")
     * )
     */
    public function requestAudit($requestId)
    {
        $logs = AuditLog::where('request_id', $requestId)
            ->with(['user'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($logs);
    }
}
