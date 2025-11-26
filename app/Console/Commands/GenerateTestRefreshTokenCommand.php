<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Features\Authentication\Models\RefreshToken;
use App\Features\Authentication\Services\TokenService;
use App\Features\UserManagement\Models\User;
use App\Shared\Enums\UserStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateTestRefreshTokenCommand extends Command
{
    protected $signature = 'test:refresh-token
                            {email : Email del usuario}
                            {--revoke : Revocar el token}
                            {--expired : Generar token expirado (para testing)}';

    protected $description = 'Generar un refresh token de prueba para testing en aplicación móvil';

    public function __construct(
        protected TokenService $tokenService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $email = $this->argument('email');
        $shouldRevoke = $this->option('revoke');
        $shouldExpire = $this->option('expired');

        // Buscar usuario
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Usuario con email '{$email}' no encontrado.");
            return self::FAILURE;
        }

        if ($user->status !== UserStatus::ACTIVE) {
            $this->error("El usuario {$email} está {$user->status->label()}. Por favor, actívalo primero.");
            return self::FAILURE;
        }

        $this->info("Generando refresh token para: {$user->email}");

        // Generar token
        $deviceInfo = [
            'device_name' => 'Mobile Test Device',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mobile Testing App',
        ];

        $result = $this->tokenService->createRefreshToken($user, $deviceInfo);
        $plainToken = $result['token'];
        $tokenModel = $result['model'];

        // Si se solicita un token expirado, crear con fecha muy cercana
        // Esto cumple con la constraint (expires_at > created_at) pero expira casi inmediatamente
        if ($shouldExpire) {
            // Sobrescribir el modelo para usar una fecha que expira en 1 segundo
            // Usar SQL UPDATE directo para saltarse validaciones de Eloquent
            DB::table('auth.refresh_tokens')
                ->where('id', $tokenModel->id)
                ->update([
                    'expires_at' => now()->subMinutes(1),
                    'created_at' => now()->subMinutes(2), // Hacer que created_at sea más antiguo
                ]);

            // Recargar el modelo desde la BD
            $tokenModel->refresh();
        }

        // Mostrar información
        $this->newLine();
        $this->info('╔════════════════════════════════════════════════════════════════════╗');
        $this->info('║                   REFRESH TOKEN GENERADO                           ║');
        $this->info('╚════════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->line("<fg=cyan>📧 Usuario:</> <fg=yellow>{$user->email}</>");
        $this->line("<fg=cyan>🆔 User ID:</> <fg=yellow>{$user->id}</>");
        $this->line("<fg=cyan>📱 Device:</> <fg=yellow>{$deviceInfo['device_name']}</>");

        $expirationStatus = $shouldExpire ? '<fg=red>EXPIRADO</>' : "<fg=yellow>{$tokenModel->expires_at->diffForHumans()}</>";
        $daysStatus = $shouldExpire ? '<fg=red>-1 días (expirado)</>' : "<fg=yellow>{$tokenModel->getDaysUntilExpiration()} días</>";

        $this->line("<fg=cyan>🕐 Expira en:</> {$expirationStatus}");
        $this->line("<fg=cyan>⏰ Estado:</> {$daysStatus}");

        $this->newLine();
        $this->warn('═══════════════════════════════════════════════════════════════════');
        $this->line('<fg=green;options=bold>TOKEN (cópialo para testing):</>');
        $this->warn('═══════════════════════════════════════════════════════════════════');
        $this->newLine();
        $this->line("<fg=yellow>{$plainToken}</>");
        $this->newLine();
        $this->warn('═══════════════════════════════════════════════════════════════════');

        $this->newLine();
        $this->info('💡 Instrucciones para testing:');
        $this->line('1. Copia el token anterior');
        $this->line('2. En tu app móvil, usa este token en el header o cookie');
        $this->line('3. Para pasar el token en header (recomendado):');
        $this->line('   <fg=cyan>X-Refresh-Token: ' . substr($plainToken, 0, 20) . '...</>');
        $this->line('4. Para validar que funciona, haz un POST a: /api/auth/refresh');
        $this->newLine();

        $this->newLine();

        if ($shouldExpire) {
            $this->info('✓ Token expirado generado exitosamente');
            $this->line('Intenta usarlo en /api/auth/refresh para testear error 401');
        } elseif ($shouldRevoke) {
            $this->warn('Revocando el token...');
            $tokenModel->revoke('Revoked by test command');
            $this->info('✓ Token revocado exitosamente');
            $this->line('Ahora puedes testear el comportamiento con un token inválido');
        } else {
            $this->info('✓ Token válido generado exitosamente');
            $this->line('Puedes usarlo inmediatamente en /api/auth/refresh');
            $this->newLine();
            $this->info('Para generar uno expirado:');
            $this->line("<fg=cyan>docker compose exec app php artisan test:refresh-token {$email} --expired</>");
            $this->newLine();
            $this->info('Para generar uno y revocarlo:');
            $this->line("<fg=cyan>docker compose exec app php artisan test:refresh-token {$email} --revoke</>");
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════════');

        return self::SUCCESS;
    }
}
