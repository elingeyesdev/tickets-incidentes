<?php

namespace App\Features\AuditLog\Database\Seeders;

use App\Features\AuditLog\Models\ActivityLog;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use App\Shared\Enums\UserStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * ActivityLog Demo Seeder
 *
 * Crea un usuario PLATFORM_ADMIN de prueba con logs de todas las acciones del sistema.
 * Útil para probar la visualización del timeline de actividad.
 *
 * Usuario de prueba:
 * - Email: demo-admin@helpdesk.test
 * - Password: demo12345
 * - Rol: PLATFORM_ADMIN
 */
class ActivityLogDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = 'demo-admin@helpdesk.test';

        // Verificar si el usuario ya existe
        $existingUser = User::where('email', $email)->first();
        
        if ($existingUser) {
            $this->info("✓ Usuario demo ya existe: {$email}");
            $this->info("  Limpiando logs anteriores y recreando...");
            
            // Limpiar logs anteriores del usuario
            ActivityLog::where('user_id', $existingUser->id)->delete();
            $user = $existingUser;
        } else {
            // Crear usuario de prueba
            $user = User::create([
                'user_code' => 'USR-DEMO-ADMIN',
                'email' => $email,
                'password_hash' => Hash::make('demo12345'),
                'email_verified' => true,
                'email_verified_at' => now(),
                'status' => UserStatus::ACTIVE,
                'auth_provider' => 'local',
                'terms_accepted' => true,
                'terms_accepted_at' => now(),
                'terms_version' => 'v2.1',
                'onboarding_completed_at' => now(),
            ]);

            // Crear perfil del usuario
            $user->profile()->create([
                'first_name' => 'Demo',
                'last_name' => 'Platform Admin',
                'phone_number' => '+591 70000000',
                'theme' => 'light',
                'language' => 'es',
                'timezone' => 'America/La_Paz',
            ]);

            // Asignar rol PLATFORM_ADMIN
            UserRole::create([
                'user_id' => $user->id,
                'role_code' => 'PLATFORM_ADMIN',
                'company_id' => null,
                'is_active' => true,
            ]);

            $this->info("✅ Usuario demo creado: {$email} (PLATFORM_ADMIN)");
        }

        // Generar logs de prueba
        $this->generateDemoLogs($user);

        $this->info("✅ Logs de actividad generados para: {$email}");
        $this->info("   Credenciales: {$email} / demo12345");
    }
    
    /**
     * Output info message safely
     */
    private function info(string $message): void
    {
        if ($this->command) {
            $this->command->info($message);
        } else {
            echo $message . PHP_EOL;
        }
    }

    /**
     * Generar logs de prueba para todas las acciones del sistema
     */
    private function generateDemoLogs(User $user): void
    {
        $userId = $user->id;
        $baseTime = now()->subDays(7); // Empezar hace 7 días
        
        $logs = [];

        // ==================== AUTHENTICATION ACTIONS ====================
        
        // 1. Register
        $logs[] = $this->createLog($userId, 'register', $baseTime, [
            'entity_type' => 'user',
            'entity_id' => $userId,
            'new_values' => ['email' => $user->email],
        ]);

        // 2. Email Verified
        $logs[] = $this->createLog($userId, 'email_verified', $baseTime->copy()->addHour(), [
            'entity_type' => 'user',
            'entity_id' => $userId,
        ]);

        // 3. Login
        $logs[] = $this->createLog($userId, 'login', $baseTime->copy()->addHours(2), [
            'metadata' => [
                'device' => 'Chrome 120 on Windows',
                'location' => 'La Paz, Bolivia',
            ],
        ]);

        // 4. Profile Updated
        $logs[] = $this->createLog($userId, 'profile_updated', $baseTime->copy()->addHours(3), [
            'entity_type' => 'user',
            'entity_id' => $userId,
            'old_values' => ['theme' => 'light'],
            'new_values' => ['theme' => 'dark'],
        ]);

        // 5. Logout
        $logs[] = $this->createLog($userId, 'logout', $baseTime->copy()->addHours(4));

        // 6. Login Failed (simular intento fallido)
        $logs[] = $this->createLog(null, 'login_failed', $baseTime->copy()->addDays(1), [
            'metadata' => [
                'email' => 'attacker@example.com',
                'reason' => 'Invalid credentials',
            ],
        ]);

        // 7. Login nuevamente
        $logs[] = $this->createLog($userId, 'login', $baseTime->copy()->addDays(1)->addHour());

        // 8. Password Reset Requested
        $logs[] = $this->createLog($userId, 'password_reset_requested', $baseTime->copy()->addDays(2), [
            'entity_type' => 'user',
            'entity_id' => $userId,
            'metadata' => ['email' => $user->email],
        ]);

        // 9. Password Changed
        $logs[] = $this->createLog($userId, 'password_changed', $baseTime->copy()->addDays(2)->addMinutes(5), [
            'entity_type' => 'user',
            'entity_id' => $userId,
            'metadata' => ['method' => 'reset'],
        ]);

        // ==================== TICKET ACTIONS ====================
        
        $fakeTicketId = Str::uuid()->toString();
        $fakeTicketCode = 'TKT-2024-001234';

        // 10. Ticket Created
        $logs[] = $this->createLog($userId, 'ticket_created', $baseTime->copy()->addDays(3), [
            'entity_type' => 'ticket',
            'entity_id' => $fakeTicketId,
            'new_values' => [
                'ticket_code' => $fakeTicketCode,
                'title' => 'No puedo acceder al sistema ERP',
                'priority' => 'HIGH',
                'company_id' => Str::uuid()->toString(),
            ],
        ]);

        // 11. Ticket Updated
        $logs[] = $this->createLog($userId, 'ticket_updated', $baseTime->copy()->addDays(3)->addHour(), [
            'entity_type' => 'ticket',
            'entity_id' => $fakeTicketId,
            'old_values' => [
                'ticket_code' => $fakeTicketCode,
                'priority' => 'HIGH',
            ],
            'new_values' => [
                'ticket_code' => $fakeTicketCode,
                'priority' => 'CRITICAL',
            ],
        ]);

        // 12. Ticket Assigned
        $fakeAgentId = Str::uuid()->toString();
        $logs[] = $this->createLog($userId, 'ticket_assigned', $baseTime->copy()->addDays(3)->addHours(2), [
            'entity_type' => 'ticket',
            'entity_id' => $fakeTicketId,
            'metadata' => [
                'ticket_code' => $fakeTicketCode,
                'assigned_to' => $fakeAgentId,
                'agent_name' => 'Carlos Mendoza',
            ],
        ]);

        // 13. Ticket Response Added
        $logs[] = $this->createLog($userId, 'ticket_response_added', $baseTime->copy()->addDays(3)->addHours(3), [
            'entity_type' => 'ticket',
            'entity_id' => $fakeTicketId,
            'metadata' => [
                'ticket_code' => $fakeTicketCode,
                'response_id' => Str::uuid()->toString(),
                'is_internal' => false,
            ],
        ]);

        // 14. Ticket Attachment Added
        $logs[] = $this->createLog($userId, 'ticket_attachment_added', $baseTime->copy()->addDays(3)->addHours(4), [
            'entity_type' => 'ticket',
            'entity_id' => $fakeTicketId,
            'new_values' => [
                'ticket_code' => $fakeTicketCode,
                'attachment_id' => Str::uuid()->toString(),
                'file_name' => 'error_screenshot.png',
                'file_url' => '/storage/attachments/error_screenshot.png',
                'mime_type' => 'image/png',
                'file_size' => 723456,
            ],
        ]);

        // 15. Ticket Resolved
        $logs[] = $this->createLog($userId, 'ticket_resolved', $baseTime->copy()->addDays(4), [
            'entity_type' => 'ticket',
            'entity_id' => $fakeTicketId,
            'metadata' => [
                'ticket_code' => $fakeTicketCode,
                'resolution_note' => 'Se reinició el servicio de autenticación y se resolvió el problema.',
            ],
        ]);

        // 16. Ticket Reopened
        $logs[] = $this->createLog($userId, 'ticket_reopened', $baseTime->copy()->addDays(4)->addHours(5), [
            'entity_type' => 'ticket',
            'entity_id' => $fakeTicketId,
            'metadata' => [
                'ticket_code' => $fakeTicketCode,
                'reopen_reason' => 'El problema volvió a ocurrir.',
            ],
        ]);

        // 17. Ticket Closed
        $logs[] = $this->createLog($userId, 'ticket_closed', $baseTime->copy()->addDays(5), [
            'entity_type' => 'ticket',
            'entity_id' => $fakeTicketId,
            'metadata' => [
                'ticket_code' => $fakeTicketCode,
                'close_note' => 'Resuelto definitivamente después de actualización.',
            ],
        ]);

        // 18. Otro Ticket - Ticket Deleted
        $fakeTicket2Id = Str::uuid()->toString();
        $logs[] = $this->createLog($userId, 'ticket_deleted', $baseTime->copy()->addDays(5)->addHours(2), [
            'entity_type' => 'ticket',
            'entity_id' => $fakeTicket2Id,
            'old_values' => [
                'ticket_code' => 'TKT-2024-001235',
                'title' => 'Ticket duplicado - borrar',
            ],
        ]);

        // ==================== USER MANAGEMENT ACTIONS ====================
        
        $fakeTargetUserId = Str::uuid()->toString();
        $fakeTargetUserName = 'Carlos Mendoza';
        $fakeTargetUserEmail = 'carlos.mendoza@empresa.com';

        // 19. User Status Changed
        $logs[] = $this->createLog($userId, 'user_status_changed', $baseTime->copy()->addDays(5)->addHours(4), [
            'entity_type' => 'user',
            'entity_id' => $fakeTargetUserId,
            'old_values' => [
                'status' => 'PENDING',
                'user_name' => $fakeTargetUserName,
                'user_email' => $fakeTargetUserEmail,
            ],
            'new_values' => [
                'status' => 'ACTIVE',
                'user_name' => $fakeTargetUserName,
                'user_email' => $fakeTargetUserEmail,
            ],
        ]);

        // 20. Role Assigned
        $fakeCompanyName = 'TechCorp S.R.L.';
        $logs[] = $this->createLog($userId, 'role_assigned', $baseTime->copy()->addDays(5)->addHours(5), [
            'entity_type' => 'user',
            'entity_id' => $fakeTargetUserId,
            'new_values' => [
                'role' => 'AGENT',
                'company_id' => Str::uuid()->toString(),
                'company_name' => $fakeCompanyName,
                'user_name' => $fakeTargetUserName,
                'user_email' => $fakeTargetUserEmail,
            ],
        ]);

        // 21. Role Removed
        $logs[] = $this->createLog($userId, 'role_removed', $baseTime->copy()->addDays(6), [
            'entity_type' => 'user',
            'entity_id' => $fakeTargetUserId,
            'old_values' => [
                'role' => 'COMPANY_ADMIN',
                'company_id' => Str::uuid()->toString(),
                'company_name' => 'OldCompany Inc.',
                'user_name' => $fakeTargetUserName,
                'user_email' => $fakeTargetUserEmail,
            ],
        ]);

        // ==================== COMPANY ACTIONS ====================
        
        $fakeCompanyRequestId = Str::uuid()->toString();
        $fakeCompanyId = Str::uuid()->toString();

        // 22. Company Request Approved
        $logs[] = $this->createLog($userId, 'company_request_approved', $baseTime->copy()->addDays(6)->addHours(2), [
            'entity_type' => 'company_request',
            'entity_id' => $fakeCompanyRequestId,
            'old_values' => ['status' => 'pending'],
            'new_values' => [
                'status' => 'approved',
                'company_name' => 'TechSolutions S.R.L.',
                'created_company_id' => $fakeCompanyId,
                'admin_email' => 'admin@techsolutions.com.bo',
            ],
        ]);

        // 23. Company Request Rejected
        $fakeCompanyRequest2Id = Str::uuid()->toString();
        $logs[] = $this->createLog($userId, 'company_request_rejected', $baseTime->copy()->addDays(6)->addHours(3), [
            'entity_type' => 'company_request',
            'entity_id' => $fakeCompanyRequest2Id,
            'old_values' => ['status' => 'pending'],
            'new_values' => [
                'status' => 'rejected',
                'company_name' => 'Empresa Sospechosa S.A.',
                'reason' => 'Documentación incompleta y datos inconsistentes.',
            ],
        ]);

        // 24. Company Created
        $logs[] = $this->createLog($userId, 'company_created', $baseTime->copy()->addDays(6)->addHours(4), [
            'entity_type' => 'company',
            'entity_id' => $fakeCompanyId,
            'new_values' => ['name' => 'TechSolutions S.R.L.'],
        ]);

        // ==================== RECENT ACTIVITY (TODAY) ====================

        // 25. Login hoy
        $logs[] = $this->createLog($userId, 'login', now()->subHours(2), [
            'metadata' => [
                'device' => 'Chrome 120 on Windows',
                'location' => 'Santa Cruz, Bolivia',
            ],
        ]);

        // 26. Ticket creado hoy
        $todayTicketId = Str::uuid()->toString();
        $logs[] = $this->createLog($userId, 'ticket_created', now()->subHour(), [
            'entity_type' => 'ticket',
            'entity_id' => $todayTicketId,
            'new_values' => [
                'ticket_code' => 'TKT-2024-001500',
                'title' => 'Consulta sobre facturación electrónica',
                'priority' => 'MEDIUM',
            ],
        ]);

        // 27. Profile updated hoy
        $logs[] = $this->createLog($userId, 'profile_updated', now()->subMinutes(30), [
            'entity_type' => 'user',
            'entity_id' => $userId,
            'old_values' => ['phone_number' => null],
            'new_values' => ['phone_number' => '+591 70000000'],
        ]);

        // Insertar todos los logs
        foreach ($logs as $log) {
            ActivityLog::create($log);
        }

        $this->info("   Total de logs generados: " . count($logs));
    }

    /**
     * Crear estructura de log
     */
    private function createLog(?string $userId, string $action, $createdAt, array $extra = []): array
    {
        return array_merge([
            'id' => Str::uuid()->toString(),
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $extra['entity_type'] ?? null,
            'entity_id' => $extra['entity_id'] ?? null,
            'old_values' => $extra['old_values'] ?? null,
            'new_values' => $extra['new_values'] ?? null,
            'metadata' => $extra['metadata'] ?? null,
            'ip_address' => '192.168.1.' . rand(1, 254),
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'created_at' => $createdAt,
        ], array_diff_key($extra, array_flip(['entity_type', 'entity_id', 'old_values', 'new_values', 'metadata'])));
    }
}
