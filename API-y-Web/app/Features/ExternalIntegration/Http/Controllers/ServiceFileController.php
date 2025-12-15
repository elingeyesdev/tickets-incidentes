<?php

namespace App\Features\ExternalIntegration\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

/**
 * Service File Controller
 * 
 * Proporciona endpoints de almacenamiento de archivos para microservicios.
 * Usa Laravel Storage, permitiendo fácil migración a S3 u otros proveedores.
 */
class ServiceFileController extends Controller
{
    /**
     * Disco de almacenamiento a usar
     * Puede ser 'local', 'public', 's3', etc.
     */
    private string $disk = 'public';

    /**
     * Directorio base para archivos de microservicios
     */
    private string $baseDir = 'microservices';

    /**
     * Upload a file
     */
    #[OA\Post(
        path: '/api/files/upload',
        summary: 'Upload a file',
        description: 'Uploads a file and returns its key and URL. Used by microservices for attachments.',
        security: [['bearerAuth' => []]],
        tags: ['External - Files'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'File to upload'),
                        new OA\Property(property: 'folder', type: 'string', description: 'Subfolder (e.g., chat, notes)', example: 'chat'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'File uploaded successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'key', type: 'string', example: 'microservices/chat/abc123.pdf'),
                        new OA\Property(property: 'url', type: 'string', example: 'http://localhost:8000/storage/microservices/chat/abc123.pdf'),
                        new OA\Property(property: 'filename', type: 'string', example: 'document.pdf'),
                        new OA\Property(property: 'mime_type', type: 'string', example: 'application/pdf'),
                        new OA\Property(property: 'size', type: 'integer', example: 102400),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'No file provided'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function upload(Request $request): JsonResponse
    {
        if (!$request->hasFile('file')) {
            return response()->json([
                'success' => false,
                'error' => 'No file provided'
            ], 400);
        }

        $file = $request->file('file');

        // Validar archivo
        $maxSize = 10 * 1024; // 10MB en KB
        if ($file->getSize() > $maxSize * 1024) {
            return response()->json([
                'success' => false,
                'error' => 'File too large. Maximum size is 10MB'
            ], 422);
        }

        // Generar nombre único
        $folder = $request->input('folder', 'general');
        $safeFolderName = preg_replace('/[^a-zA-Z0-9_-]/', '', $folder);
        $directory = "{$this->baseDir}/{$safeFolderName}";

        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;

        // Guardar archivo
        $path = $file->storeAs($directory, $filename, $this->disk);

        // Obtener URL
        $url = Storage::disk($this->disk)->url($path);

        return response()->json([
            'success' => true,
            'key' => $path,
            'url' => $url,
            'filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ], 201);
    }

    /**
     * Download/Get file info
     */
    #[OA\Get(
        path: '/api/files/{key}',
        summary: 'Get file info or download',
        description: 'Returns file information or redirects to download URL.',
        security: [['bearerAuth' => []]],
        tags: ['External - Files'],
        parameters: [
            new OA\Parameter(name: 'key', in: 'path', required: true, description: 'File key/path', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'download', in: 'query', description: 'If true, returns download response', schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'File info',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'key', type: 'string'),
                        new OA\Property(property: 'url', type: 'string'),
                        new OA\Property(property: 'size', type: 'integer'),
                        new OA\Property(property: 'mime_type', type: 'string'),
                        new OA\Property(property: 'last_modified', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'File not found'),
        ]
    )]
    public function show(Request $request, string $key): JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Decodificar key (puede venir URL-encoded)
        $key = urldecode($key);

        if (!Storage::disk($this->disk)->exists($key)) {
            return response()->json([
                'success' => false,
                'error' => 'File not found'
            ], 404);
        }

        // Si solicita descarga
        if ($request->boolean('download')) {
            return Storage::disk($this->disk)->download($key);
        }

        // Retornar info
        return response()->json([
            'key' => $key,
            'url' => Storage::disk($this->disk)->url($key),
            'size' => Storage::disk($this->disk)->size($key),
            'mime_type' => Storage::disk($this->disk)->mimeType($key),
            'last_modified' => date('c', Storage::disk($this->disk)->lastModified($key)),
        ]);
    }

    /**
     * Delete a file
     */
    #[OA\Delete(
        path: '/api/files/{key}',
        summary: 'Delete a file',
        description: 'Deletes a file from storage.',
        security: [['bearerAuth' => []]],
        tags: ['External - Files'],
        parameters: [
            new OA\Parameter(name: 'key', in: 'path', required: true, description: 'File key/path', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'File deleted',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'File deleted successfully'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'File not found'),
        ]
    )]
    public function destroy(string $key): JsonResponse
    {
        $key = urldecode($key);

        if (!Storage::disk($this->disk)->exists($key)) {
            return response()->json([
                'success' => false,
                'error' => 'File not found'
            ], 404);
        }

        Storage::disk($this->disk)->delete($key);

        return response()->json([
            'success' => true,
            'message' => 'File deleted successfully'
        ]);
    }

    /**
     * List files in a folder
     */
    #[OA\Get(
        path: '/api/files',
        summary: 'List files in a folder',
        description: 'Lists all files in a specific microservice folder.',
        security: [['bearerAuth' => []]],
        tags: ['External - Files'],
        parameters: [
            new OA\Parameter(name: 'folder', in: 'query', description: 'Subfolder to list', schema: new OA\Schema(type: 'string', example: 'chat')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Files list',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'folder', type: 'string'),
                        new OA\Property(property: 'files', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'total', type: 'integer'),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $folder = $request->input('folder', 'general');
        $safeFolderName = preg_replace('/[^a-zA-Z0-9_-]/', '', $folder);
        $directory = "{$this->baseDir}/{$safeFolderName}";

        $files = Storage::disk($this->disk)->files($directory);

        $fileList = array_map(function ($file) {
            return [
                'key' => $file,
                'url' => Storage::disk($this->disk)->url($file),
                'size' => Storage::disk($this->disk)->size($file),
                'last_modified' => date('c', Storage::disk($this->disk)->lastModified($file)),
            ];
        }, $files);

        return response()->json([
            'folder' => $directory,
            'files' => $fileList,
            'total' => count($fileList),
        ]);
    }
}
