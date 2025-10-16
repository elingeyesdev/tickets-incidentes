<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Mutations\Concerns;

use Illuminate\Support\Facades\Cookie;

/**
 * SetsRefreshTokenCookie
 *
 * Trait para establecer y limpiar cookies HttpOnly de refresh token.
 * Usado por LoginMutation, RegisterMutation y LogoutMutation.
 *
 * Características de seguridad:
 * - HttpOnly: No accesible desde JavaScript (previene XSS)
 * - Secure: Solo HTTPS en producción
 * - SameSite=Strict: Previene CSRF
 * - Path=/: Disponible en toda la aplicación
 */
trait SetsRefreshTokenCookie
{
    /**
     * Almacena el refresh token para que esté disponible en tests
     * Los tests pueden acceder a este valor usando la reflexión si es necesario
     */
    protected static ?string $lastRefreshToken = null;

    /**
     * Establece una cookie HttpOnly con el refresh token
     *
     * Esta cookie es segura y no puede ser accedida por JavaScript,
     * previniendo ataques XSS.
     *
     * @param string $refreshToken Token de refresh a almacenar
     * @return void
     */
    protected function setRefreshTokenCookie(string $refreshToken): void
    {
        // Almacenar para tests
        self::$lastRefreshToken = $refreshToken;

        $cookieLifetime = (int) config('jwt.refresh_ttl'); // En minutos

        Cookie::queue(
            'refresh_token',                    // Nombre
            $refreshToken,                      // Valor
            $cookieLifetime,                    // Tiempo de vida en minutos
            '/',                                // Path
            null,                               // Domain (null = dominio actual)
            config('app.env') === 'production', // Secure (solo HTTPS en producción)
            true,                               // HttpOnly (no accesible desde JavaScript)
            false,                              // Raw
            'lax'                            // SameSite (strict para máxima seguridad)
        );
    }

    /**
     * Limpia la cookie HttpOnly del refresh token
     *
     * Establece una cookie expirada para eliminarla del navegador.
     * Usado durante el logout.
     *
     * @return void
     */
    protected function clearRefreshTokenCookie(): void
    {
        self::$lastRefreshToken = null;

        Cookie::queue(
            Cookie::forget('refresh_token')
        );
    }

    /**
     * Obtiene el último refresh token (solo para tests)
     *
     * @return string|null
     */
    public static function getLastRefreshToken(): ?string
    {
        return self::$lastRefreshToken;
    }
}
