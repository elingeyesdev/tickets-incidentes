<?php declare(strict_types=1);

namespace Tests\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * Handles Time Travel With Cache
 *
 * Trait profesional para tests que necesitan travelTo() + Cache con expiración robusta.
 *
 * PROBLEMA:
 * - Cuando se hace travelTo(), Laravel mockea el time() internamente
 * - Pero Redis (proceso separado) mantiene su propio reloj real
 * - Las keys en Redis NO expiran automáticamente cuando travelTo() se ejecuta
 * - Esto causa que tests con rate-limiting fallen incorrectamente
 *
 * SOLUCIÓN:
 * - Este trait sobrepone travelTo() para automáticamente expirar keys de Redis
 * - Después de viajar en el tiempo, detecta keys que deberían estar expiradas
 * - Y las deleta manualmente para sincronizar con el tiempo mockado
 *
 * MECANISMO:
 * 1. Mantiene registro del tiempo base (antes de viajar)
 * 2. Cuando travelTo() se ejecuta, calcula cuánto tiempo "viajó"
 * 3. Para cada key en Redis, revisa si debería estar expirada según:
 *    - El TTL reportado por Redis
 *    - El tiempo viajado
 * 4. Deleta automáticamente keys que han expirado
 *
 * TRANSPARENCIA:
 * - Completamente automático y transparente
 * - No requiere cambios en los services
 * - No requiere modificar los tests existentes
 * - Funciona solo con Redis (el backend robusto)
 *
 * @author Claude (AI Assistant)
 * @date 2025-10-28
 */
trait HandlesTimeTravelWithCache
{
    /**
     * Timestamp base antes de cualquier time travel
     */
    private static ?int $baseTime = null;

    /**
     * Override de travelTo() que sincroniza Redis con el tiempo mockado
     *
     * Después de cambiar el tiempo, automáticamente expira las keys de Redis
     * que deberían haber expirado según el nuevo tiempo mockado.
     *
     * @param $date
     * @param $callback
     * @return mixed
     */
    public function travelTo($date, $callback = null)
    {
        // Registrar el tiempo base al primer travelTo()
        if (self::$baseTime === null) {
            self::$baseTime = now()->timestamp;
        }

        // Llamar al travelTo() original de Laravel
        $result = parent::travelTo($date, $callback);

        // Sincronizar Redis con el nuevo tiempo mockado
        if (config('cache.default') === 'redis' && app()->environment('testing')) {
            $this->synchronizeRedisWithMockedTime();
        }

        return $result;
    }

    /**
     * Sincronizar Redis con el tiempo mockado de Laravel
     *
     * Revisa todas las keys en Redis y expira aquellas que deberían estar
     * expiradas según el tiempo actual mockado.
     *
     * La expiración se detecta cuando:
     * - Se sabe cuándo se almacenó la key (mediante análisis de TTL)
     * - Se compara con el tiempo actual mockado
     * - Si tiempo_actual >= tiempo_almacenado + TTL_original, deletear
     *
     * @return void
     */
    private function synchronizeRedisWithMockedTime(): void
    {
        try {
            $prefix = config('cache.prefix', '');
            $currentTime = now()->timestamp;
            $timeTraveled = $currentTime - self::$baseTime;

            // Patrones específicos de keys que típicamente usan TTL y son afectadas por time-travel
            $timeSensitivePatterns = [
                'password_reset*',      // Password reset tokens y códigos
                'rate_limit*',          // Rate limiting
                'session*',             // Sesiones con expiración
                'remember_*',           // Remember me tokens
            ];

            foreach ($timeSensitivePatterns as $pattern) {
                $fullPattern = $prefix . $pattern;
                $keys = Redis::keys($fullPattern);

                foreach ($keys as $fullKey) {
                    $ttl = Redis::ttl($fullKey);

                    // TTL en Redis:
                    // > 0: segundos hasta expiración
                    // -1: key existe sin expiración
                    // -2: key no existe

                    // Ignorar keys sin expiración o inexistentes
                    if ($ttl <= 0 && $ttl !== -1) {
                        continue; // Ya está expirada
                    }

                    if ($ttl === -1) {
                        continue; // Sin expiración
                    }

                    // LÓGICA DE EXPIRACIÓN MEJORADA:
                    // Si viajamos hacia adelante en el tiempo, los keys con TTL pequeño probablemente expiraron
                    // Heurística: Si TTL < tiempo viajado, la key ha expirado

                    if ($timeTraveled > 0 && $ttl > 0) {
                        // Si el TTL es menor que el tiempo que viajamos hacia adelante,
                        // es probable que la key haya expirado
                        // Ejemplo:
                        //   - Key almacenada con TTL 60 en time = T0
                        //   - Viajamos a T0 + 70 segundos
                        //   - Redis reporta TTL = 60 (no sabe que viajamos)
                        //   - Pero TTL debería ser = 60 - 70 = -10 (expirado)
                        //   - Condición: TTL (60) < timeTraveled (70) → Expirar

                        if ($ttl < $timeTraveled) {
                            Redis::del($fullKey);
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            // Redis podría no estar disponible en algunos contextos de testing
            // Simplemente continuar sin sincronización
            \Log::debug('Could not synchronize Redis with mocked time: ' . $e->getMessage());
        }
    }

    /**
     * Resetear el tiempo base al iniciar un nuevo test
     */
    protected function setUp(): void
    {
        parent::setUp();
        self::$baseTime = null;
    }
}
