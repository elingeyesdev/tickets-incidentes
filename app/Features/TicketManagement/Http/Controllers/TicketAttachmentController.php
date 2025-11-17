<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Controllers;

use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketAttachment;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\TicketManagement\Http\Requests\UploadAttachmentRequest;
use App\Features\TicketManagement\Http\Resources\TicketAttachmentResource;
use App\Features\TicketManagement\Services\AttachmentService;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use OpenApi\Attributes as OA;

/**
 * Ticket Attachment Controller
 *
 * Handles file attachment operations for tickets:
 * - Upload attachments to tickets or responses
 * - List all attachments for a ticket
 * - Delete attachments (within 30 minutes by uploader)
 * - Download attachment files
 *
 * Authorization is handled by TicketAttachmentPolicy.
 * File validation is handled by AttachmentService.
 *
 * Supported file types (16 total):
 * - Documents: pdf, txt, log, doc, docx, xls, xlsx, csv
 * - Images: jpg, jpeg, png, gif, bmp, webp, svg
 * - Videos: mp4
 *
 * Constraints:
 * - Maximum file size: 10 MB
 * - Maximum 5 attachments per ticket
 * - Delete only within 30 minutes of upload
 *
 * Feature: Ticket Management
 * Base URL: /api/tickets/{ticket}/attachments
 */
class TicketAttachmentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private AttachmentService $attachmentService
    ) {}

    #[OA\Post(
        path: '/api/tickets/{ticket}/attachments',
        operationId: 'upload_ticket_attachment',
        description: 'Uploads a file attachment to a ticket. Supports multipart/form-data uploads with file validation. Maximum file size is 10 MB. Only 5 attachments are allowed per ticket. Allowed file types: pdf, txt, log, doc, docx, xls, xlsx, csv (documents), jpg, jpeg, png, gif, bmp, webp, svg (images), mp4 (video). Optionally, a response_id can be provided to attach the file to a specific ticket response. Authorization: ticket creator or AGENT from ticket\'s company. Cannot upload to closed tickets.',
        summary: 'Upload attachment to ticket',
        security: [
            ['bearerAuth' => []],
        ],
        requestBody: new OA\RequestBody(
            description: 'File upload with optional response association',
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(
                            property: 'file',
                            description: 'File to upload (max 10MB). Allowed types: pdf, txt, log, doc, docx, xls, xlsx, csv, jpg, jpeg, png, gif, bmp, webp, svg, mp4',
                            type: 'string',
                            format: 'binary'
                        ),
                        new OA\Property(
                            property: 'response_id',
                            description: 'Optional UUID of a ticket response to attach the file to. Must belong to this ticket. Only response author can upload within 30 minutes of response creation.',
                            type: 'string',
                            format: 'uuid',
                            example: '550e8400-e29b-41d4-a716-446655440001',
                            nullable: true
                        ),
                    ],
                    type: 'object'
                )
            )
        ),
        tags: ['Ticket Attachments'],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'Ticket code (e.g., TKT-2025-00001)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^TKT-\d{4}-\d{5}$', example: 'TKT-2025-00001')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'File uploaded successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            description: 'Success message',
                            type: 'string',
                            example: 'Archivo subido exitosamente'
                        ),
                        new OA\Property(
                            property: 'data',
                            description: 'Uploaded attachment resource',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '770e8400-e29b-41d4-a716-446655440000'),
                                new OA\Property(property: 'ticket_id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
                                new OA\Property(property: 'response_id', type: 'string', format: 'uuid', example: null, nullable: true),
                                new OA\Property(property: 'uploaded_by_user_id', type: 'string', format: 'uuid', example: '660e8400-e29b-41d4-a716-446655440000'),
                                new OA\Property(property: 'uploaded_by_name', type: 'string', example: 'John Doe'),
                                new OA\Property(property: 'file_name', type: 'string', example: 'screenshot.png'),
                                new OA\Property(property: 'file_url', type: 'string', example: 'tickets/attachments/1731774123_screenshot.png'),
                                new OA\Property(property: 'file_type', type: 'string', example: 'png'),
                                new OA\Property(property: 'file_size_bytes', type: 'integer', example: 245760),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-16T14:30:00+00:00'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request - generic upload failure',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Failed to upload attachment'),
                        new OA\Property(property: 'error', type: 'string', example: 'Storage write failed'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - user not authorized to upload or ticket is closed',
                content: new OA\JsonContent(
                    oneOf: [
                        new OA\Schema(
                            properties: [
                                new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.'),
                            ],
                            type: 'object'
                        ),
                        new OA\Schema(
                            properties: [
                                new OA\Property(property: 'code', type: 'string', example: 'TICKET_CLOSED'),
                                new OA\Property(property: 'message', type: 'string', example: 'Cannot upload attachments to a closed ticket.'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Ticket not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'No query results for model [Ticket].'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 413,
                description: 'Payload too large - file exceeds 10 MB limit',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'file',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'File size must not exceed 10 MB.')
                                ),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error - invalid file type, max attachments exceeded, or invalid response_id',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            example: [
                                'file' => ['Invalid file type.'],
                                'response_id' => ['The selected response does not belong to this ticket.'],
                            ]
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Get(
        path: '/api/tickets/{ticket}/attachments',
        operationId: 'list_ticket_attachments',
        description: 'Retrieves all attachments for a ticket, including those attached to responses. Returns attachment metadata with uploader information. Attachments are ordered by creation date (oldest first). Authorization: ticket creator or AGENT from ticket\'s company can view attachments.',
        summary: 'List all ticket attachments',
        security: [
            ['bearerAuth' => []],
        ],
        tags: ['Ticket Attachments'],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'Ticket code (e.g., TKT-2025-00001)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^TKT-\d{4}-\d{5}$', example: 'TKT-2025-00001')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of attachments retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            description: 'Array of attachment resources',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '770e8400-e29b-41d4-a716-446655440000'),
                                    new OA\Property(property: 'ticket_id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
                                    new OA\Property(property: 'response_id', type: 'string', format: 'uuid', example: null, nullable: true),
                                    new OA\Property(property: 'uploaded_by_user_id', type: 'string', format: 'uuid', example: '660e8400-e29b-41d4-a716-446655440000'),
                                    new OA\Property(property: 'uploaded_by_name', type: 'string', example: 'John Doe'),
                                    new OA\Property(property: 'file_name', type: 'string', example: 'error_log.txt'),
                                    new OA\Property(property: 'file_url', type: 'string', example: 'tickets/attachments/1731774123_error_log.txt'),
                                    new OA\Property(property: 'file_type', type: 'string', example: 'txt'),
                                    new OA\Property(property: 'file_size_bytes', type: 'integer', example: 1024),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-16T14:30:00+00:00'),
                                ],
                                type: 'object'
                            ),
                            example: [
                                [
                                    'id' => '770e8400-e29b-41d4-a716-446655440000',
                                    'ticket_id' => '550e8400-e29b-41d4-a716-446655440000',
                                    'response_id' => null,
                                    'uploaded_by_user_id' => '660e8400-e29b-41d4-a716-446655440000',
                                    'uploaded_by_name' => 'John Doe',
                                    'file_name' => 'error_log.txt',
                                    'file_url' => 'tickets/attachments/1731774123_error_log.txt',
                                    'file_type' => 'txt',
                                    'file_size_bytes' => 1024,
                                    'created_at' => '2025-11-16T14:30:00+00:00',
                                ],
                                [
                                    'id' => '880e8400-e29b-41d4-a716-446655440000',
                                    'ticket_id' => '550e8400-e29b-41d4-a716-446655440000',
                                    'response_id' => '550e8400-e29b-41d4-a716-446655440001',
                                    'uploaded_by_user_id' => '770e8400-e29b-41d4-a716-446655440000',
                                    'uploaded_by_name' => 'Jane Smith',
                                    'file_name' => 'solution_screenshot.png',
                                    'file_url' => 'tickets/attachments/1731774456_solution_screenshot.png',
                                    'file_type' => 'png',
                                    'file_size_bytes' => 524288,
                                    'created_at' => '2025-11-16T15:00:00+00:00',
                                ],
                            ]
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - user not authorized to view attachments',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Ticket not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'No query results for model [Ticket].'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Post(
        path: '/api/tickets/{ticket}/responses/{response}/attachments',
        operationId: 'upload_response_attachment',
        description: 'Uploads a file attachment to a specific ticket response. Only the response author can upload attachments, and only within 30 minutes of response creation. Same file constraints apply: 10 MB max size, 5 attachments max per ticket, allowed file types. Useful for adding supporting documentation or evidence to a response after it was created.',
        summary: 'Upload attachment to specific response',
        security: [
            ['bearerAuth' => []],
        ],
        requestBody: new OA\RequestBody(
            description: 'File to upload to the response',
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(
                            property: 'file',
                            description: 'File to upload (max 10MB). Allowed types: pdf, txt, log, doc, docx, xls, xlsx, csv, jpg, jpeg, png, gif, bmp, webp, svg, mp4',
                            type: 'string',
                            format: 'binary'
                        ),
                    ],
                    type: 'object'
                )
            )
        ),
        tags: ['Ticket Attachments'],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'Ticket code (e.g., TKT-2025-00001)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^TKT-\d{4}-\d{5}$', example: 'TKT-2025-00001')
            ),
            new OA\Parameter(
                name: 'response',
                description: 'UUID of the ticket response to attach the file to',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440001')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'File uploaded to response successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            description: 'Success message',
                            type: 'string',
                            example: 'Archivo subido exitosamente'
                        ),
                        new OA\Property(
                            property: 'data',
                            description: 'Uploaded attachment resource with response_id set',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '990e8400-e29b-41d4-a716-446655440000'),
                                new OA\Property(property: 'ticket_id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
                                new OA\Property(property: 'response_id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440001'),
                                new OA\Property(property: 'uploaded_by_user_id', type: 'string', format: 'uuid', example: '660e8400-e29b-41d4-a716-446655440000'),
                                new OA\Property(property: 'uploaded_by_name', type: 'string', example: 'John Doe'),
                                new OA\Property(property: 'file_name', type: 'string', example: 'config_backup.xlsx'),
                                new OA\Property(property: 'file_url', type: 'string', example: 'tickets/attachments/1731774789_config_backup.xlsx'),
                                new OA\Property(property: 'file_type', type: 'string', example: 'xlsx'),
                                new OA\Property(property: 'file_size_bytes', type: 'integer', example: 98304),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-16T16:00:00+00:00'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request - generic upload failure',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Failed to upload attachment'),
                        new OA\Property(property: 'error', type: 'string', example: 'Storage write failed'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - not response author, outside 30-minute window, or ticket is closed',
                content: new OA\JsonContent(
                    oneOf: [
                        new OA\Schema(
                            properties: [
                                new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.'),
                            ],
                            type: 'object'
                        ),
                        new OA\Schema(
                            properties: [
                                new OA\Property(property: 'code', type: 'string', example: 'TICKET_CLOSED'),
                                new OA\Property(property: 'message', type: 'string', example: 'Cannot upload attachments to a closed ticket.'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Ticket or response not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'No query results for model [TicketResponse].'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 413,
                description: 'Payload too large - file exceeds 10 MB limit',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'file',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'File size must not exceed 10 MB.')
                                ),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error - response does not belong to ticket, invalid file type, or max attachments exceeded',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            example: [
                                'response_id' => ['The selected response does not belong to this ticket.'],
                                'file' => ['Invalid file type.'],
                            ]
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Delete(
        path: '/api/tickets/{ticket}/attachments/{attachment}',
        operationId: 'delete_ticket_attachment',
        description: 'Deletes an attachment from a ticket. Only the original uploader can delete the attachment, and only within 30 minutes of upload. The file is removed from storage and the database record is deleted. Cannot delete attachments from closed tickets.',
        summary: 'Delete a ticket attachment',
        security: [
            ['bearerAuth' => []],
        ],
        tags: ['Ticket Attachments'],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'Ticket code (e.g., TKT-2025-00001)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^TKT-\d{4}-\d{5}$', example: 'TKT-2025-00001')
            ),
            new OA\Parameter(
                name: 'attachment',
                description: 'UUID of the attachment to delete',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '770e8400-e29b-41d4-a716-446655440000')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Attachment deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            description: 'Success message',
                            type: 'string',
                            example: 'Archivo eliminado exitosamente'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request - deletion failed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Failed to delete attachment'),
                        new OA\Property(property: 'error', type: 'string', example: 'Storage deletion failed'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - not uploader, outside 30-minute window, or ticket is closed',
                content: new OA\JsonContent(
                    oneOf: [
                        new OA\Schema(
                            properties: [
                                new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.'),
                            ],
                            type: 'object'
                        ),
                        new OA\Schema(
                            properties: [
                                new OA\Property(property: 'code', type: 'string', example: 'TICKET_CLOSED'),
                                new OA\Property(property: 'message', type: 'string', example: 'Cannot delete attachments from a closed ticket.'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Ticket or attachment not found, or attachment does not belong to ticket',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Attachment does not belong to this ticket.'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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

    #[OA\Get(
        path: '/api/tickets/attachments/{attachment}/download',
        operationId: 'download_ticket_attachment',
        description: 'Downloads an attachment file. Returns the actual file as a binary stream with appropriate Content-Disposition header for browser download. The file is downloaded with its original filename. Authorization: ticket creator or AGENT from ticket\'s company can download attachments. Files are stored locally on the server.',
        summary: 'Download an attachment file',
        security: [
            ['bearerAuth' => []],
        ],
        tags: ['Ticket Attachments'],
        parameters: [
            new OA\Parameter(
                name: 'attachment',
                description: 'UUID of the attachment to download',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '770e8400-e29b-41d4-a716-446655440000')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'File download stream',
                headers: [
                    new OA\Header(
                        header: 'Content-Disposition',
                        description: 'File attachment with original filename',
                        schema: new OA\Schema(type: 'string', example: 'attachment; filename="error_log.txt"')
                    ),
                    new OA\Header(
                        header: 'Content-Type',
                        description: 'MIME type of the file',
                        schema: new OA\Schema(type: 'string', example: 'application/octet-stream')
                    ),
                    new OA\Header(
                        header: 'Content-Length',
                        description: 'Size of the file in bytes',
                        schema: new OA\Schema(type: 'integer', example: 1024)
                    ),
                ],
                content: new OA\MediaType(
                    mediaType: 'application/octet-stream',
                    schema: new OA\Schema(
                        type: 'string',
                        format: 'binary'
                    )
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - user not authorized to download attachments',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Attachment not found or file does not exist in storage',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'File not found'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
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
