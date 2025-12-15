<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Features\Authentication\Models\RefreshToken;
use App\Features\Authentication\Services\TokenService;
use App\Features\UserManagement\Models\User;
use App\Shared\Enums\UserStatus;
use Firebase\JWT\JWT;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateTestRefreshTokenCommand extends Command
{
    protected $signature = 'test:refresh-token
                            {email : Email del usuario}
                            {--revoke : Revocar el refresh token}
                            {--expired-access : Generar access token expirado (para testing)}';

    protected $description = 'Generar tokens (access + refresh) para testing en aplicación móvil';

    public function __construct(
        protected TokenService $tokenService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $email = $this->argument('email');
        $shouldRevoke = $this->option('revoke');
        $shouldExpireAccess = $this->option('expired-access');

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

        $this->info("Generando tokens para: {$user->email}");

        // Generar refresh token
        $deviceInfo = [
            'device_name' => 'Mobile Test Device',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mobile Testing App',
        ];

        $result = $this->tokenService->createRefreshToken($user, $deviceInfo);
        $refreshTokenPlain = $result['token'];
        $refreshTokenModel = $result['model'];

        // Generar access token con el refresh token ID como session_id
        $accessToken = $this->tokenService->generateAccessToken($user, $refreshTokenModel->id);

        // Si se solicita, crear un access token expirado
        if ($shouldExpireAccess) {
            $accessToken = $this->generateExpiredAccessToken($user, $refreshTokenModel->id);
        }

        // Si se solicita, revocar el refresh token
        if ($shouldRevoke) {
            $refreshTokenModel->revoke('Revoked by test command');
        }

        // Mostrar información
        $this->newLine();
        $this->info('╔════════════════════════════════════════════════════════════════════╗');
        $this->info('║                     TOKENS GENERADOS PARA TESTING                  ║');
        $this->info('╚════════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->line("<fg=cyan>📧 Usuario:</> <fg=yellow>{$user->email}</>");
        $this->line("<fg=cyan>🆔 User ID:</> <fg=yellow>{$user->id}</>");
        $this->line("<fg=cyan>📱 Device:</> <fg=yellow>{$deviceInfo['device_name']}</>");

        $refreshStatus = $shouldRevoke ? '<fg=red>REVOCADO</>' : '<fg=green>✓ VÁLIDO</>';
        $accessStatus = $shouldExpireAccess ? '<fg=red>EXPIRADO</>' : '<fg=green>✓ VÁLIDO</>';

        $this->newLine();
        $this->line("<fg=cyan>🔄 Refresh Token:</> {$refreshStatus}");
        $this->line("<fg=cyan>⏰ Refresh Expira:</> <fg=yellow>{$refreshTokenModel->expires_at->diffForHumans()}</>");
        $this->newLine();
        $this->line("<fg=cyan>🎫 Access Token:</> {$accessStatus}");
        $this->line("<fg=cyan>⏰ Access Expira:</> " . ($shouldExpireAccess ? '<fg=red>HACE 1 HORA</>' : '<fg=yellow>en 60 minutos</>'));

        $this->newLine();
        $this->newLine();

        // Mostrar REFRESH TOKEN
        $this->warn('═══════════════════════════════════════════════════════════════════');
        $this->line('<fg=green;options=bold>REFRESH TOKEN (cópialo para testing):</>');
        $this->warn('═══════════════════════════════════════════════════════════════════');
        $this->newLine();
        $this->line("<fg=yellow>{$refreshTokenPlain}</>");
        $this->newLine();

        // Mostrar ACCESS TOKEN
        $this->warn('═══════════════════════════════════════════════════════════════════');
        $this->line('<fg=green;options=bold>ACCESS TOKEN (JWT):</>');
        $this->warn('═══════════════════════════════════════════════════════════════════');
        $this->newLine();
        $this->line("<fg=yellow>{$accessToken}</>");
        $this->newLine();
        $this->warn('═══════════════════════════════════════════════════════════════════');

        $this->newLine();
        $this->info('💡 Instrucciones para testing:');
        $this->newLine();

        $this->line('1️⃣  <fg=cyan>Para testear refresh con access token expirado:</>');
        $this->line('   • Usa el ACCESS TOKEN en el header Authorization: Bearer [token]');
        $this->line('   • Luego usa el REFRESH TOKEN para refrescar');
        $this->newLine();

        $this->line('2️⃣  <fg=cyan>En tu app móvil, envía a POST /api/auth/refresh:</>');
        $this->line('   • Header: <fg=cyan>X-Refresh-Token: ' . substr($refreshTokenPlain, 0, 20) . '...</>');
        $this->line('   • O en Cookie: <fg=cyan>refresh_token: [token]</>');
        $this->newLine();

        $this->line('3️⃣  <fg=cyan>Response esperado (si todo funciona):</>');
        $this->line('   • Status: <fg=green>200 OK</>');
        $this->line('   • Body: <fg=cyan>{accessToken, tokenType, expiresIn}</>');

        $this->newLine();
        $this->newLine();

        if ($shouldExpireAccess) {
            $this->info('✓ Access Token <fg=red>EXPIRADO</> generado');
            $this->line('Úsalo en header Authorization para simular token expirado');
        } elseif ($shouldRevoke) {
            $this->info('✓ Refresh Token <fg=red>REVOCADO</> generado');
            $this->line('Intenta refrescar con este token para testear error 401');
        } else {
            $this->info('✓ Tokens <fg=green>VÁLIDOS</> generados');
            $this->line('Puedes usarlos inmediatamente para testing');
            $this->newLine();
            $this->info('Variantes disponibles:');
            $this->line("<fg=cyan>docker compose exec app php artisan test:refresh-token {$email} --expired-access</>");
            $this->line("<fg=cyan>docker compose exec app php artisan test:refresh-token {$email} --revoke</>");
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════════');

        return self::SUCCESS;
    }

    /**
     * Generar un JWT con fecha de expiración en el pasado
     */
    private function generateExpiredAccessToken(User $user, string $sessionId): string
    {
        $now = time();
        // Access token que expiró hace 1 hora
        $expiredTime = $now - 3600;

        $payload = [
            'iss' => config('jwt.issuer'),
            'aud' => config('jwt.audience'),
            'iat' => $expiredTime - 3600, // Generado hace 2 horas
            'exp' => $expiredTime, // Expiró hace 1 hora
            'sub' => $user->id,
            'user_id' => $user->id,
            'email' => $user->email,
            'session_id' => $sessionId,
            'roles' => $user->getAllRolesForJWT(),
        ];

        return JWT::encode($payload, config('jwt.secret'), config('jwt.algo'));
    }
}
