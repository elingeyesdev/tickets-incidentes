<?php

declare(strict_types=1);

namespace App\Features\AuditLog\Services;

use App\Features\AuditLog\Models\ActivityLog;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * ActivityLogService
 *
 * Servicio para registrar actividad del sistema.
 * Utiliza Redis para buffering y mejorar rendimiento.
 */
class ActivityLogService
{
    /**
     * Buffer de logs en Redis
     */
    private const REDIS_BUFFER_KEY = 'activity_log:buffer';
    private const REDIS_BUFFER_SIZE = 50; // Flush cada 50 registros
    private const REDIS_BUFFER_TTL = 60;  // O cada 60 segundos

    /**
     * Registrar una actividad
     */
    public function log(
        string $action,
        ?string $userId = null,
        ?string $entityType = null,
        ?string $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        $logEntry = [
            'id' => Str::uuid()->toString(),
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
            'created_at' => now()->toDateTimeString(),
        ];

        // Intentar usar Redis buffer, si falla escribir directo
        if ($this->shouldUseRedisBuffer()) {
            $this->addToBuffer($logEntry);
        } else {
            $this->writeDirectly($logEntry);
        }
    }

    /**
     * Registrar actividad desde el contexto HTTP actual
     */
    public function logFromRequest(
        string $action,
        ?string $entityType = null,
        ?string $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null
    ): void {
        $userId = null;

        try {
            if (auth()->check()) {
                $userId = auth()->id();
            }
        } catch (\Exception $e) {
            // Sin usuario autenticado
        }

        $this->log(
            action: $action,
            userId: $userId,
            entityType: $entityType,
            entityId: $entityId,
            oldValues: $oldValues,
            newValues: $newValues,
            metadata: $metadata,
            ipAddress: request()->ip(),
            userAgent: request()->userAgent()
        );
    }

    /**
     * Verificar si debemos usar Redis buffer
     */
    private function shouldUseRedisBuffer(): bool
    {
        try {
            return config('audit.use_redis_buffer', true) && Redis::connection();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Agregar entrada al buffer de Redis
     */
    private function addToBuffer(array $logEntry): void
    {
        try {
            Redis::rpush(self::REDIS_BUFFER_KEY, json_encode($logEntry));

            // Verificar si hay que hacer flush
            $bufferSize = Redis::llen(self::REDIS_BUFFER_KEY);

            if ($bufferSize >= self::REDIS_BUFFER_SIZE) {
                $this->flushBuffer();
            }
        } catch (\Exception $e) {
            // Si falla Redis, escribir directo
            $this->writeDirectly($logEntry);
        }
    }

    /**
     * Flush del buffer de Redis a la base de datos
     */
    public function flushBuffer(): int
    {
        $flushed = 0;

        try {
            $entries = [];
            
            // Obtener todas las entradas del buffer
            while ($entry = Redis::lpop(self::REDIS_BUFFER_KEY)) {
                $decoded = json_decode($entry, true);
                if ($decoded) {
                    $entries[] = $decoded;
                }
                $flushed++;
            }

            // Insertar en batch
            if (!empty($entries)) {
                $this->writeBatch($entries);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to flush activity log buffer', [
                'error' => $e->getMessage(),
            ]);
        }

        return $flushed;
    }

    /**
     * Escribir directamente a la base de datos
     */
    private function writeDirectly(array $logEntry): void
    {
        try {
            ActivityLog::create([
                'id' => $logEntry['id'],
                'user_id' => $logEntry['user_id'],
                'action' => $logEntry['action'],
                'entity_type' => $logEntry['entity_type'],
                'entity_id' => $logEntry['entity_id'],
                'old_values' => $logEntry['old_values'] ? json_decode($logEntry['old_values'], true) : null,
                'new_values' => $logEntry['new_values'] ? json_decode($logEntry['new_values'], true) : null,
                'metadata' => $logEntry['metadata'] ? json_decode($logEntry['metadata'], true) : null,
                'ip_address' => $logEntry['ip_address'],
                'user_agent' => $logEntry['user_agent'],
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to write activity log', [
                'error' => $e->getMessage(),
                'action' => $logEntry['action'],
            ]);
        }
    }

    /**
     * Escribir batch de registros
     */
    private function writeBatch(array $entries): void
    {
        $records = array_map(function ($entry) {
            return [
                'id' => $entry['id'],
                'user_id' => $entry['user_id'],
                'action' => $entry['action'],
                'entity_type' => $entry['entity_type'],
                'entity_id' => $entry['entity_id'],
                'old_values' => $entry['old_values'],
                'new_values' => $entry['new_values'],
                'metadata' => $entry['metadata'],
                'ip_address' => $entry['ip_address'],
                'user_agent' => $entry['user_agent'],
                'created_at' => $entry['created_at'],
            ];
        }, $entries);

        \DB::table('audit.activity_logs')->insert($records);
    }

    /**
     * Obtener actividad de un usuario con paginación
     */
    public function getUserActivity(
        string $userId,
        ?string $action = null,
        ?string $category = null,
        int $perPage = 15
    ) {
        $query = ActivityLog::forUser($userId)
            ->orderBy('created_at', 'desc');

        if ($action) {
            $query->forAction($action);
        }

        if ($category) {
            match ($category) {
                'authentication' => $query->authActions(),
                'tickets' => $query->ticketActions(),
                'users' => $query->userActions(),
                'companies' => $query->companyActions(),
                default => null,
            };
        }

        return $query->paginate($perPage);
    }

    /**
     * Obtener actividad de una entidad específica
     */
    public function getEntityActivity(
        string $entityType,
        string $entityId,
        int $perPage = 15
    ) {
        return ActivityLog::forEntity($entityType, $entityId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Limpiar registros antiguos (política de retención)
     */
    public function cleanOldRecords(int $days = 90): int
    {
        $cutoffDate = now()->subDays($days);

        return ActivityLog::where('created_at', '<', $cutoffDate)->delete();
    }

    // ==================== MÉTODOS DE CONVENIENCIA ====================

    /**
     * Registrar login exitoso
     */
    public function logLogin(string $userId, array $deviceInfo = []): void
    {
        $this->log(
            action: 'login',
            userId: $userId,
            metadata: $deviceInfo
        );
    }

    /**
     * Registrar login fallido
     */
    public function logLoginFailed(string $email, ?string $reason = null): void
    {
        $this->log(
            action: 'login_failed',
            metadata: [
                'email' => $email,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Registrar logout
     */
    public function logLogout(string $userId): void
    {
        $this->log(
            action: 'logout',
            userId: $userId
        );
    }

    /**
     * Registrar creación de ticket
     */
    public function logTicketCreated(string $userId, string $ticketId, array $ticketData): void
    {
        $this->log(
            action: 'ticket_created',
            userId: $userId,
            entityType: 'ticket',
            entityId: $ticketId,
            newValues: $ticketData
        );
    }

    /**
     * Registrar actualización de ticket
     */
    public function logTicketUpdated(string $userId, string $ticketId, array $oldData, array $newData): void
    {
        $this->log(
            action: 'ticket_updated',
            userId: $userId,
            entityType: 'ticket',
            entityId: $ticketId,
            oldValues: $oldData,
            newValues: $newData
        );
    }

    /**
     * Registrar resolución de ticket
     */
    public function logTicketResolved(string $userId, string $ticketId, ?string $note = null): void
    {
        $this->log(
            action: 'ticket_resolved',
            userId: $userId,
            entityType: 'ticket',
            entityId: $ticketId,
            metadata: $note ? ['resolution_note' => $note] : null
        );
    }

    /**
     * Registrar cierre de ticket
     */
    public function logTicketClosed(string $userId, string $ticketId, ?string $note = null): void
    {
        $this->log(
            action: 'ticket_closed',
            userId: $userId,
            entityType: 'ticket',
            entityId: $ticketId,
            metadata: $note ? ['close_note' => $note] : null
        );
    }

    /**
     * Registrar reapertura de ticket
     */
    public function logTicketReopened(string $userId, string $ticketId, ?string $reason = null): void
    {
        $this->log(
            action: 'ticket_reopened',
            userId: $userId,
            entityType: 'ticket',
            entityId: $ticketId,
            metadata: $reason ? ['reopen_reason' => $reason] : null
        );
    }

    /**
     * Registrar asignación de ticket
     */
    public function logTicketAssigned(string $userId, string $ticketId, string $agentId): void
    {
        $this->log(
            action: 'ticket_assigned',
            userId: $userId,
            entityType: 'ticket',
            entityId: $ticketId,
            metadata: ['assigned_to' => $agentId]
        );
    }

    /**
     * Registrar respuesta agregada
     */
    public function logTicketResponseAdded(string $userId, string $ticketId, string $responseId): void
    {
        $this->log(
            action: 'ticket_response_added',
            userId: $userId,
            entityType: 'ticket',
            entityId: $ticketId,
            metadata: ['response_id' => $responseId]
        );
    }

    /**
     * Registrar cambio de estado de usuario
     */
    public function logUserStatusChanged(string $adminId, string $targetUserId, string $oldStatus, string $newStatus): void
    {
        $this->log(
            action: 'user_status_changed',
            userId: $adminId,
            entityType: 'user',
            entityId: $targetUserId,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $newStatus]
        );
    }

    /**
     * Registrar asignación de rol
     */
    public function logRoleAssigned(string $adminId, string $targetUserId, string $roleCode, ?string $companyId = null): void
    {
        $this->log(
            action: 'role_assigned',
            userId: $adminId,
            entityType: 'user',
            entityId: $targetUserId,
            newValues: [
                'role' => $roleCode,
                'company_id' => $companyId,
            ]
        );
    }

    /**
     * Registrar remoción de rol
     */
    public function logRoleRemoved(string $adminId, string $targetUserId, string $roleCode, ?string $companyId = null): void
    {
        $this->log(
            action: 'role_removed',
            userId: $adminId,
            entityType: 'user',
            entityId: $targetUserId,
            oldValues: [
                'role' => $roleCode,
                'company_id' => $companyId,
            ]
        );
    }

    /**
     * Registrar aprobación de solicitud de empresa
     */
    public function logCompanyRequestApproved(
        string $adminId,
        string $requestId,
        string $companyName,
        string $createdCompanyId,
        string $adminEmail
    ): void {
        $this->log(
            action: 'company_request_approved',
            userId: $adminId,
            entityType: 'company_request',
            entityId: $requestId,
            oldValues: ['status' => 'pending'],
            newValues: [
                'status' => 'approved',
                'company_name' => $companyName,
                'created_company_id' => $createdCompanyId,
                'admin_email' => $adminEmail,
            ]
        );
    }

    /**
     * Registrar rechazo de solicitud de empresa
     */
    public function logCompanyRequestRejected(
        string $adminId,
        string $requestId,
        string $companyName,
        string $reason
    ): void {
        $this->log(
            action: 'company_request_rejected',
            userId: $adminId,
            entityType: 'company_request',
            entityId: $requestId,
            oldValues: ['status' => 'pending'],
            newValues: [
                'status' => 'rejected',
                'company_name' => $companyName,
                'reason' => $reason,
            ]
        );
    }

    // ==================== AUTHENTICATION ACTIONS ====================

    /**
     * Registrar registro de usuario
     */
    public function logRegister(string $userId, string $email): void
    {
        $this->log(
            action: 'register',
            userId: $userId,
            entityType: 'user',
            entityId: $userId,
            newValues: ['email' => $email]
        );
    }

    /**
     * Registrar verificación de email
     */
    public function logEmailVerified(string $userId): void
    {
        $this->log(
            action: 'email_verified',
            userId: $userId,
            entityType: 'user',
            entityId: $userId
        );
    }

    /**
     * Registrar solicitud de reset de password
     */
    public function logPasswordResetRequested(?string $userId, string $email): void
    {
        $this->log(
            action: 'password_reset_requested',
            userId: $userId,
            entityType: 'user',
            entityId: $userId,
            metadata: ['email' => $email]
        );
    }

    /**
     * Registrar cambio de password
     */
    public function logPasswordChanged(string $userId, string $method = 'reset'): void
    {
        $this->log(
            action: 'password_changed',
            userId: $userId,
            entityType: 'user',
            entityId: $userId,
            metadata: ['method' => $method]
        );
    }

    // ==================== ADDITIONAL TICKET ACTIONS ====================

    /**
     * Registrar eliminación de ticket
     */
    public function logTicketDeleted(string $userId, string $ticketId, array $ticketData = []): void
    {
        $this->log(
            action: 'ticket_deleted',
            userId: $userId,
            entityType: 'ticket',
            entityId: $ticketId,
            oldValues: $ticketData
        );
    }

    /**
     * Registrar adjunto agregado a ticket
     */
    public function logTicketAttachmentAdded(string $userId, string $ticketId, string $attachmentId, ?string $fileName = null): void
    {
        $this->log(
            action: 'ticket_attachment_added',
            userId: $userId,
            entityType: 'ticket',
            entityId: $ticketId,
            newValues: [
                'attachment_id' => $attachmentId,
                'file_name' => $fileName,
            ]
        );
    }

    // ==================== COMPANY ACTIONS ====================

    /**
     * Registrar creación de empresa
     */
    public function logCompanyCreated(string $userId, string $companyId, string $companyName): void
    {
        $this->log(
            action: 'company_created',
            userId: $userId,
            entityType: 'company',
            entityId: $companyId,
            newValues: ['name' => $companyName]
        );
    }

    /**
     * Registrar actualización de perfil
     */
    public function logProfileUpdated(string $userId, ?array $oldValues = null, ?array $newValues = null): void
    {
        $this->log(
            action: 'profile_updated',
            userId: $userId,
            entityType: 'user',
            entityId: $userId,
            oldValues: $oldValues,
            newValues: $newValues
        );
    }
}
