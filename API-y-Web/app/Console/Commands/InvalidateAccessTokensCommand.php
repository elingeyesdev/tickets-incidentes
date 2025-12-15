<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Features\Authentication\Services\TokenService;
use App\Features\UserManagement\Models\User;
use App\Shared\Enums\UserStatus;
use Illuminate\Console\Command;

class InvalidateAccessTokensCommand extends Command
{
    protected $signature = 'test:invalidate-access-tokens
                            {email : Email del usuario}
                            {--confirm : Confirmar sin preguntar}';

    protected $description = 'Invalidar todos los access tokens de un usuario para testing de refresh flow';

    public function __construct(
        protected TokenService $tokenService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $email = $this->argument('email');
        $shouldConfirm = $this->option('confirm');

        // Buscar usuario
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Usuario con email '{$email}' no encontrado.");
            return self::FAILURE;
        }

        // Mostrar advertencia
        $this->newLine();
        $this->warn('╔════════════════════════════════════════════════════════════════════╗');
        $this->warn('║              ⚠️  INVALIDAR TODOS LOS ACCESS TOKENS                  ║');
        $this->warn('╚════════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->line("<fg=cyan>📧 Usuario:</> <fg=yellow>{$user->email}</>");
        $this->line("<fg=cyan>🆔 User ID:</> <fg=yellow>{$user->id}</>");

        $this->newLine();
        $this->line('Esto invalidará TODOS los access tokens activos de este usuario.');
        $this->line('Los refresh tokens seguirán siendo válidos para obtener nuevos access tokens.');

        $this->newLine();

        // Confirmar
        if (!$shouldConfirm) {
            if (!$this->confirm('¿Deseas continuar?')) {
                $this->info('Operación cancelada.');
                return self::SUCCESS;
            }
        }

        // Ejecutar invalidación
        $this->info('Invalidando todos los access tokens...');

        $this->tokenService->blacklistUser($user->id);

        $this->newLine();
        $this->info('╔════════════════════════════════════════════════════════════════════╗');
        $this->info('║                  ✓ INVALIDACIÓN COMPLETADA                        ║');
        $this->info('╚════════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->line('<fg=green>✓ Todos los access tokens han sido invalidados</>');
        $this->newLine();

        $this->info('💡 Próximos pasos para testing:');
        $this->newLine();
        $this->line('1️⃣  <fg=cyan>En tu app móvil:</>');
        $this->line('   • Intenta hacer cualquier request con el access token anterior');
        $this->line('   • Resultado esperado: <fg=red>401 Unauthorized<//>');
        $this->newLine();

        $this->line('2️⃣  <fg=cyan>Luego refresca con el refresh token:</>');
        $this->line('   • POST /api/auth/refresh');
        $this->line('   • Header: <fg=cyan>X-Refresh-Token: [refresh_token]</>');
        $this->line('   • Resultado: <fg=green>200 OK con nuevo access token</>');
        $this->newLine();

        $this->line('3️⃣  <fg=cyan>Ahora vuelve a intentar el request original</>');
        $this->line('   • Con el nuevo access token');
        $this->line('   • Resultado esperado: <fg=green>200 OK</>');

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════════');

        return self::SUCCESS;
    }
}
