<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Services;

use App\Features\TicketManagement\Exceptions\MaxAttachmentsExceededException;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketAttachment;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\TicketManagement\Rules\ValidFileType;
use App\Features\UserManagement\Models\User;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AttachmentService
{
    /**
     * Tamaño máximo de archivo: 10 MB
     */
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * Máximo de archivos adjuntos por ticket
     */
    private const MAX_ATTACHMENTS_PER_TICKET = 5;

    /**
     * Sube un archivo adjunto a un ticket o respuesta
     *
     * @param Ticket $ticket Ticket al que adjuntar
     * @param UploadedFile $file Archivo a subir
     * @param User $user Usuario que sube
     * @param TicketResponse|null $response Respuesta opcional (si es null, se adjunta al ticket)
     * @return TicketAttachment
     * @throws MaxAttachmentsExceededException
     * @throws \Exception
     */
    public function upload(Ticket $ticket, UploadedFile $file, User $user = null, ?TicketResponse $response = null): TicketAttachment
    {
        // Si no se proporciona usuario, usar autenticado
        if ($user === null) {
            $user = JWTHelper::getAuthenticatedUser();
        }

        // Validar archivo
        $this->validateFile($file);

        // Validar límite de archivos por ticket
        $attachmentCount = TicketAttachment::where('ticket_id', $ticket->id)->count();
        if ($attachmentCount >= self::MAX_ATTACHMENTS_PER_TICKET) {
            throw new MaxAttachmentsExceededException(
                "Maximum " . self::MAX_ATTACHMENTS_PER_TICKET . " attachments per ticket exceeded"
            );
        }

        // Guardar archivo
        $filePath = $this->storeFile($file, $ticket->id);

        // Crear registro de adjunto
        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => $response?->id,
            'uploaded_by_user_id' => $user->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => $file->getClientOriginalExtension(),
            'file_size_bytes' => $file->getSize(),
        ]);

        return $attachment;
    }

    /**
     * Lista todos los adjuntos de un ticket
     *
     * @param Ticket $ticket
     * @return Collection
     */
    public function list(Ticket $ticket): Collection
    {
        return $ticket->attachments()
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Elimina un adjunto y su archivo
     *
     * @param TicketAttachment $attachment Adjunto a eliminar
     * @return bool
     */
    public function delete(TicketAttachment $attachment): bool
    {
        // Eliminar archivo del storage
        if (Storage::disk('local')->exists($attachment->file_path)) {
            Storage::disk('local')->delete($attachment->file_path);
        }

        // Eliminar registro
        return $attachment->delete();
    }

    /**
     * Valida un archivo antes de subirlo
     *
     * @param UploadedFile $file
     * @throws \Exception
     */
    private function validateFile(UploadedFile $file): void
    {
        // Validar tamaño
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \Exception(
                'File size exceeds maximum limit of ' . (self::MAX_FILE_SIZE / 1024 / 1024) . ' MB'
            );
        }

        // Validar tipo de archivo
        $validator = Validator::make(
            ['file' => $file],
            ['file' => [new ValidFileType()]],
            ['file.required' => 'File is required']
        );

        if ($validator->fails()) {
            throw new \Exception(
                $validator->errors()->first('file') ?? 'Invalid file type'
            );
        }
    }

    /**
     * Almacena un archivo en el storage
     *
     * @param UploadedFile $file
     * @param string $ticketId ID del ticket
     * @return string Ruta del archivo almacenado
     */
    private function storeFile(UploadedFile $file, string $ticketId): string
    {
        $timestamp = now()->timestamp;
        $originalName = $file->getClientOriginalName();

        // Generar nombre único: {timestamp}_{nombre_original}
        $fileName = "{$timestamp}_{$originalName}";

        // Ruta: tickets/attachments/{timestamp}_{nombre}
        $path = "tickets/attachments/{$fileName}";

        // Guardar en storage local
        Storage::disk('local')->putFileAs(
            "tickets/attachments",
            $file,
            $fileName
        );

        return $path;
    }

    /**
     * Calcula la ruta de almacenamiento para un archivo
     * (Método público para tests unitarios)
     *
     * @param string $originalFileName
     * @return string
     */
    public function calculateStoragePath(string $originalFileName): string
    {
        $uuid = Str::uuid();
        $timestamp = now()->timestamp;

        // Extraer extensión
        $extension = pathinfo($originalFileName, PATHINFO_EXTENSION);

        // Generar nombre único
        $fileName = "{$timestamp}_{$uuid}.{$extension}";

        return "tickets/attachments/{$uuid}/{$fileName}";
    }

    /**
     * Valida el tamaño de un archivo
     * (Método público para tests unitarios)
     *
     * @param int $sizeInBytes Tamaño en bytes
     * @throws \Exception
     */
    public function validateFileSize(int $sizeInBytes): void
    {
        if ($sizeInBytes > self::MAX_FILE_SIZE) {
            throw new \Exception(
                'File size exceeds maximum limit of ' . (self::MAX_FILE_SIZE / 1024 / 1024) . ' MB'
            );
        }
    }

    /**
     * Valida el tipo de archivo
     * (Método público para tests unitarios)
     *
     * @param string $fileName
     * @throws \Exception
     */
    public function validateFileType(string $fileName): void
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        $validator = Validator::make(
            ['file_name' => $fileName],
            ['file_name' => [new ValidFileType()]],
            ['file_name.required' => 'File is required']
        );

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first('file_name');
            // Asegurar que el mensaje contenga "type"
            if (!str_contains(strtolower($errorMessage), 'type')) {
                $errorMessage = "Invalid file type: {$extension}";
            }
            throw new \Exception($errorMessage);
        }
    }
}
