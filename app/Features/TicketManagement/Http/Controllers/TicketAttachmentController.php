<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Controllers;

use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketAttachment;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\TicketManagement\Requests\UploadAttachmentRequest;
use App\Features\TicketManagement\Resources\TicketAttachmentResource;
use App\Features\TicketManagement\Services\AttachmentService;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private AttachmentService $attachmentService
    ) {}

    /**
     * POST /api/tickets/{ticket}/attachments
     *
     * Upload attachment directly to ticket.
     * Optionally upload to a specific response via response_id parameter.
     */
    public function store(UploadAttachmentRequest $request, Ticket $ticket): JsonResponse
    {
        // Authorize: only ticket creator or company agent can upload
        $this->authorize('upload', [TicketAttachment::class, $ticket]);

        // Validate ticket is NOT closed
        if ($ticket->status === TicketStatus::CLOSED) {
            return response()->json([
                'code' => 'TICKET_CLOSED',
                'message' => 'Cannot upload attachments to a closed ticket.',
            ], 403);
        }

        // Get authenticated user
        $user = JWTHelper::getAuthenticatedUser();

        // Get the file
        $file = $request->file('file');

        // Get the response if provided
        $response = null;
        if ($request->input('response_id')) {
            $responseId = $request->input('response_id');
            $response = TicketResponse::findOrFail($responseId);

            // Validate response belongs to this ticket
            if ($response->ticket_id !== $ticket->id) {
                return response()->json([
                    'message' => 'The selected response does not belong to this ticket.',
                    'errors' => [
                        'response_id' => ['The selected response does not belong to this ticket.'],
                    ],
                ], 422);
            }

            // Authorize upload to response: only response author within 30 minutes
            $this->authorize('uploadToResponse', [TicketAttachment::class, $response]);
        }

        try {
            // Call service to upload
            $attachment = $this->attachmentService->upload($ticket, $file, $user, $response);

            return response()->json([
                'message' => 'Archivo subido exitosamente',
                'data' => new TicketAttachmentResource($attachment),
            ], 200);
        } catch (\Exception $e) {
            // Handle validation errors from service
            $message = $e->getMessage();

            // Check for specific error types
            if (str_contains($message, 'File size exceeds')) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => ['file' => ['File size must not exceed 10 MB.']],
                ], 413);
            }

            if (str_contains($message, 'type')) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => ['file' => ['Invalid file type.']],
                ], 422);
            }

            if (str_contains($message, 'Maximum')) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => ['file' => ['Maximum 5 attachments per ticket.']],
                ], 422);
            }

            // Generic error
            return response()->json([
                'message' => 'Failed to upload attachment',
                'error' => $message,
            ], 400);
        }
    }

    /**
     * GET /api/tickets/{ticket}/attachments
     *
     * List all attachments for a ticket (including response attachments).
     */
    public function index(Ticket $ticket): JsonResponse
    {
        // Authorize: only ticket creator or company agent can view
        $this->authorize('viewAny', [TicketAttachment::class, $ticket]);

        // Get attachments from service
        $attachments = $this->attachmentService->list($ticket);

        // Load relationships
        $attachments->load('uploader', 'uploader.profile');

        return response()->json([
            'data' => TicketAttachmentResource::collection($attachments),
        ]);
    }

    /**
     * POST /api/tickets/{ticket}/responses/{response}/attachments
     *
     * Upload attachment to a specific response.
     */
    public function storeToResponse(
        UploadAttachmentRequest $request,
        Ticket $ticket,
        TicketResponse $response
    ): JsonResponse {
        // Validate response belongs to this ticket
        if ($response->ticket_id !== $ticket->id) {
            return response()->json([
                'message' => 'The selected response does not belong to this ticket.',
                'errors' => [
                    'response_id' => ['The selected response does not belong to this ticket.'],
                ],
            ], 422);
        }

        // Authorize upload to response: only response author within 30 minutes
        $this->authorize('uploadToResponse', [TicketAttachment::class, $response]);

        // Validate ticket is NOT closed
        if ($ticket->status === TicketStatus::CLOSED) {
            return response()->json([
                'code' => 'TICKET_CLOSED',
                'message' => 'Cannot upload attachments to a closed ticket.',
            ], 403);
        }

        $user = JWTHelper::getAuthenticatedUser();
        $file = $request->file('file');

        try {
            $attachment = $this->attachmentService->upload($ticket, $file, $user, $response);

            return response()->json([
                'message' => 'Archivo subido exitosamente',
                'data' => new TicketAttachmentResource($attachment),
            ], 200);
        } catch (\Exception $e) {
            $message = $e->getMessage();

            if (str_contains($message, 'File size exceeds')) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => ['file' => ['File size must not exceed 10 MB.']],
                ], 413);
            }

            if (str_contains($message, 'type')) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => ['file' => ['Invalid file type.']],
                ], 422);
            }

            if (str_contains($message, 'Maximum')) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => ['file' => ['Maximum 5 attachments per ticket.']],
                ], 422);
            }

            return response()->json([
                'message' => 'Failed to upload attachment',
                'error' => $message,
            ], 400);
        }
    }

    /**
     * DELETE /api/tickets/{ticket}/attachments/{attachment}
     *
     * Delete an attachment (uploader only, within 30 minutes).
     */
    public function destroy(Ticket $ticket, TicketAttachment $attachment): JsonResponse
    {
        // Validate attachment belongs to this ticket
        if ($attachment->ticket_id !== $ticket->id) {
            return response()->json([
                'message' => 'Attachment does not belong to this ticket.',
            ], 404);
        }

        // Authorize: only uploader within 30 minutes
        $this->authorize('delete', $attachment);

        // Validate ticket is NOT closed
        if ($ticket->status === TicketStatus::CLOSED) {
            return response()->json([
                'code' => 'TICKET_CLOSED',
                'message' => 'Cannot delete attachments from a closed ticket.',
            ], 403);
        }

        try {
            // Call service to delete
            $this->attachmentService->delete($attachment);

            return response()->json([
                'message' => 'Archivo eliminado exitosamente',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete attachment',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * GET /api/tickets/attachments/{attachment}/download
     *
     * Download an attachment file.
     */
    public function download(TicketAttachment $attachment): StreamedResponse
    {
        // Get the ticket
        $ticket = $attachment->ticket;

        // Authorize: can view ticket attachments
        $this->authorize('viewAny', [TicketAttachment::class, $ticket]);

        // Get file path
        $path = $attachment->file_path;

        // Verify file exists in storage
        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'File not found');
        }

        // Return file download
        return Storage::disk('local')->download($path, $attachment->file_name);
    }
}
