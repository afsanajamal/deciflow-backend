<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Request as RequestModel;
use App\Models\RequestAttachment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Upload an attachment to a request
     *
     * @OA\Post(
     *     path="/api/v1/requests/{id}/attachments",
     *     tags={"Attachments"},
     *     summary="Upload attachment",
     *     description="Upload a file attachment to a request",
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
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 required={"file"},
     *
     *                 @OA\Property(property="file", type="string", format="binary", description="File to upload (max 10MB)")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Attachment uploaded successfully",
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=404, description="Request not found")
     * )
     */
    public function upload(Request $request, $requestId)
    {
        $requestModel = RequestModel::findOrFail($requestId);

        // Check if user can upload to this request
        $this->authorize('update', $requestModel);

        $validated = $request->validate([
            'file' => 'required|file|max:10240', // Max 10MB
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getMimeType();

        // Generate unique filename
        $filename = uniqid().'_'.time().'.'.$extension;

        // Store file in storage/app/attachments
        $path = $file->storeAs('attachments', $filename);

        // Create attachment record
        $attachment = RequestAttachment::create([
            'request_id' => $requestId,
            'file_name' => $originalName,
            'file_path' => $path,
            'mime_type' => $mimeType,
        ]);

        return response()->json($attachment, 201);
    }

    /**
     * Download an attachment
     *
     * @OA\Get(
     *     path="/api/v1/attachments/{id}",
     *     tags={"Attachments"},
     *     summary="Download attachment",
     *     description="Download a file attachment",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Attachment ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="File download",
     *
     *         @OA\MediaType(mediaType="application/octet-stream")
     *     ),
     *
     *     @OA\Response(response=404, description="Attachment not found"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function download($id)
    {
        $attachment = RequestAttachment::findOrFail($id);
        $request = $attachment->request;

        // Check if user can view this request
        $this->authorize('view', $request);

        if (! Storage::exists($attachment->file_path)) {
            return response()->json([
                'error' => [
                    'code' => 'FILE_NOT_FOUND',
                    'message' => 'File not found',
                ],
            ], 404);
        }

        return Storage::download($attachment->file_path, $attachment->file_name);
    }

    /**
     * Delete an attachment
     *
     * @OA\Delete(
     *     path="/api/v1/attachments/{id}",
     *     tags={"Attachments"},
     *     summary="Delete attachment",
     *     description="Delete a file attachment",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Attachment ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Attachment deleted successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Attachment deleted successfully")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Attachment not found"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function destroy(Request $request, $id)
    {
        $attachment = RequestAttachment::findOrFail($id);
        $requestModel = $attachment->request;

        // Check if user can update this request
        $this->authorize('update', $requestModel);

        // Delete file from storage
        if (Storage::exists($attachment->file_path)) {
            Storage::delete($attachment->file_path);
        }

        $attachment->delete();

        return response()->json([
            'message' => 'Attachment deleted successfully',
        ]);
    }

    /**
     * List all attachments for a request
     *
     * @OA\Get(
     *     path="/api/v1/requests/{id}/attachments",
     *     tags={"Attachments"},
     *     summary="List attachments",
     *     description="Get all attachments for a request",
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
     *         description="List of attachments",
     *
     *         @OA\JsonContent(type="array", @OA\Items(type="object"))
     *     ),
     *
     *     @OA\Response(response=404, description="Request not found"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function index($requestId)
    {
        $request = RequestModel::findOrFail($requestId);

        // Check if user can view this request
        $this->authorize('view', $request);

        $attachments = $request->attachments;

        return response()->json($attachments);
    }
}
