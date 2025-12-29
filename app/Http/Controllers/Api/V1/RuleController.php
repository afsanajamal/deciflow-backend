<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Rule;
use Illuminate\Http\Request;

class RuleController extends Controller
{
    /**
     * List all rules
     *
     * @OA\Get(
     *     path="/api/v1/rules",
     *     tags={"Rules"},
     *     summary="List all rules",
     *     description="Get all approval rules (admin only)",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of rules",
     *         @OA\JsonContent(type="array", @OA\Items(type="object"))
     *     )
     * )
     */
    public function index()
    {
        $rules = Rule::orderBy('min_amount')->get();
        return response()->json($rules);
    }

    /**
     * Create a new rule
     *
     * @OA\Post(
     *     path="/api/v1/rules",
     *     tags={"Rules"},
     *     summary="Create rule",
     *     description="Create a new approval rule (admin only)",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","min_amount","approval_steps_json"},
     *             @OA\Property(property="name", type="string", example="Low value purchases"),
     *             @OA\Property(property="min_amount", type="integer", example=0, description="Minimum amount in JPY"),
     *             @OA\Property(property="max_amount", type="integer", example=100000, description="Maximum amount in JPY"),
     *             @OA\Property(
     *                 property="approval_steps_json",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 example={"dept_admin", "approver"}
     *             ),
     *             @OA\Property(property="category", type="string", example="EQUIPMENT"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Rule created successfully",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'min_amount' => 'required|integer|min:0',
            'max_amount' => 'nullable|integer|gt:min_amount',
            'approval_steps_json' => 'required|array',
            'category' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $rule = Rule::create($validated);

        return response()->json($rule, 201);
    }

    /**
     * Update a rule
     *
     * @OA\Put(
     *     path="/api/v1/rules/{id}",
     *     tags={"Rules"},
     *     summary="Update rule",
     *     description="Update an existing approval rule (admin only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Rule ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="min_amount", type="integer"),
     *             @OA\Property(property="max_amount", type="integer"),
     *             @OA\Property(property="approval_steps_json", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="category", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rule updated successfully",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=404, description="Rule not found")
     * )
     */
    public function update(Request $request, $id)
    {
        $rule = Rule::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'min_amount' => 'sometimes|integer|min:0',
            'max_amount' => 'nullable|integer',
            'approval_steps_json' => 'sometimes|array',
            'category' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $rule->update($validated);

        return response()->json($rule);
    }

    /**
     * Delete a rule
     *
     * @OA\Delete(
     *     path="/api/v1/rules/{id}",
     *     tags={"Rules"},
     *     summary="Delete rule",
     *     description="Delete an approval rule (admin only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Rule ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rule deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Rule deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Rule not found")
     * )
     */
    public function destroy($id)
    {
        $rule = Rule::findOrFail($id);
        $rule->delete();

        return response()->json(['message' => 'Rule deleted successfully']);
    }
}
