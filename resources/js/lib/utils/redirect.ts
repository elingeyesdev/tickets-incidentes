/**
 * Redirect Loop Prevention
 * Sistema para detectar y prevenir loops infinitos de redirección
 *
 * Previene situaciones donde guards redirigen continuamente entre rutas,
 * causando un stack overflow o comportamiento inesperado.
 */

/**
 * Almacena contadores de redirección por ruta
 * Key: "fromPath->toPath"
 * Value: Número de veces que se ha intentado esta redirección
 */
const redirectCounts = new Map<string, number>();

/**
 * Máximo número de redirecciones permitidas para la misma ruta
 * en el período de tiempo definido
 */
const MAX_REDIRECTS = 3;

/**
 * Tiempo en milisegundos después del cual se resetea el contador
 * (5 segundos)
 */
const RESET_TIMEOUT = 5000;

/**
 * Almacena timeouts activos para resetear contadores
 * Note: In browser environment, setTimeout returns number, not NodeJS.Timeout
 */
const activeTimeouts = new Map<string, NodeJS.Timeout>();

/**
 * Verifica si se puede redirigir sin causar un loop
 *
 * @param fromPath - Ruta actual desde donde se quiere redirigir
 * @param toPath - Ruta destino a la que se quiere redirigir
 * @returns true si es seguro redirigir, false si se detectó un loop
 *
 * @example
 * ```tsx
 * if (canRedirect(currentPath, '/dashboard')) {
 *   router.visit('/dashboard');
 * } else {
 *   console.error('Loop de redirección detectado');
 * }
 * ```
 */
export function canRedirect(fromPath: string, toPath: string): boolean {
    const key = `${fromPath}->${toPath}`;

    const count = redirectCounts.get(key) || 0;

    if (count >= MAX_REDIRECTS) {
        console.error(`🔴 LOOP DE REDIRECCIÓN DETECTADO`);
        console.error(`   Ruta: ${fromPath} -> ${toPath}`);
        console.error(`   Intentos: ${count} en los últimos ${RESET_TIMEOUT}ms`);
        console.error(`   Acción: Redirección bloqueada para prevenir loop infinito`);

        // Mostrar stack trace para debugging
        console.error('   Stack trace:', new Error().stack);

        return false;
    }

    // Incrementar contador
    redirectCounts.set(key, count + 1);

    // Cancelar timeout anterior si existe
    const existingTimeout = activeTimeouts.get(key);
    if (existingTimeout) {
        clearTimeout(existingTimeout);
    }

    // Crear nuevo timeout para resetear contador
    const timeout = setTimeout(() => {
        redirectCounts.delete(key);
        activeTimeouts.delete(key);
        console.log(`✅ Contador de redirección reseteado: ${key}`);
    }, RESET_TIMEOUT);

    activeTimeouts.set(key, timeout);

    console.log(`🔄 Redirección permitida: ${fromPath} -> ${toPath} (${count + 1}/${MAX_REDIRECTS})`);
    return true;
}

/**
 * Resetea todos los contadores de redirección
 * Útil en situaciones de testing o cuando se necesita limpiar el estado
 *
 * @example
 * ```tsx
 * // En tests
 * afterEach(() => {
 *   resetRedirectCounts();
 * });
 * ```
 */
export function resetRedirectCounts(): void {
    // Limpiar todos los timeouts activos
    activeTimeouts.forEach((timeout) => clearTimeout(timeout));
    activeTimeouts.clear();

    // Limpiar contadores
    redirectCounts.clear();

    console.log('🔄 Todos los contadores de redirección han sido reseteados');
}

/**
 * Obtiene el número de intentos de redirección para una ruta específica
 * Útil para debugging
 *
 * @param fromPath - Ruta origen
 * @param toPath - Ruta destino
 * @returns Número de intentos realizados
 */
export function getRedirectCount(fromPath: string, toPath: string): number {
    const key = `${fromPath}->${toPath}`;
    return redirectCounts.get(key) || 0;
}

/**
 * Verifica si una ruta específica está cerca del límite
 * Útil para mostrar advertencias antes de bloquear
 *
 * @param fromPath - Ruta origen
 * @param toPath - Ruta destino
 * @returns true si está cerca del límite (>= 50%)
 */
export function isNearRedirectLimit(fromPath: string, toPath: string): boolean {
    const count = getRedirectCount(fromPath, toPath);
    return count >= MAX_REDIRECTS * 0.5;
}

/**
 * Hook personalizado para usar en guards de Inertia
 * Verifica automáticamente antes de redirigir
 *
 * @example
 * ```tsx
 * const safeRedirect = useSafeRedirect();
 *
 * if (needsRedirect) {
 *   safeRedirect('/dashboard');
 * }
 * ```
 */
export function useSafeRedirect() {
    return (toPath: string) => {
        const currentPath = window.location.pathname;

        if (!canRedirect(currentPath, toPath)) {
            console.error('Redirección bloqueada por seguridad');
            return;
        }

        // Usar router de Inertia si está disponible
        if (typeof window !== 'undefined' && (window as any).Inertia) {
            (window as any).Inertia.visit(toPath);
        } else {
            // Fallback a location.href
            window.location.href = toPath;
        }
    };
}

/**
 * Obtiene estadísticas de redirecciones
 * Útil para debugging y monitoreo
 */
export function getRedirectStats() {
    const stats = Array.from(redirectCounts.entries()).map(([route, count]) => ({
        route,
        count,
        nearLimit: count >= MAX_REDIRECTS * 0.5,
        blocked: count >= MAX_REDIRECTS,
    }));

    return {
        total: redirectCounts.size,
        routes: stats,
        maxRedirects: MAX_REDIRECTS,
        resetTimeout: RESET_TIMEOUT,
    };
}