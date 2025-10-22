/**
 * Navigation Utilities
 * Helpers para navegación con protección contra loops infinitos
 */

import { router } from '@inertiajs/react';

const REDIRECT_FLAG_KEY = 'inertia_redirecting';
const REDIRECT_TIMEOUT = 5000; // 5 segundos

/**
 * Realiza una redirección única (protegida contra loops)
 * Usa SessionStorage para persistir el flag durante re-montajes
 */
export const safeRedirect = (url: string, options?: { replace?: boolean }): boolean => {
    // Verificar si ya hay una redirección en progreso
    const redirecting = sessionStorage.getItem(REDIRECT_FLAG_KEY);

    if (redirecting) {
        console.log('[safeRedirect] Redirección ya en progreso, ignorando');
        return false;
    }

    // Marcar que estamos redirigiendo
    sessionStorage.setItem(REDIRECT_FLAG_KEY, url);
    console.log('[safeRedirect] Iniciando redirección a:', url);

    // Limpiar el flag después del timeout (por si la navegación falla)
    setTimeout(() => {
        sessionStorage.removeItem(REDIRECT_FLAG_KEY);
        console.log('[safeRedirect] Flag limpiado por timeout');
    }, REDIRECT_TIMEOUT);

    // Realizar la redirección
    // Note: Inertia v2 router.replace() is for client-side only updates
    // For navigation with replace, use visit() with replace option
    router.visit(url, { replace: options?.replace ?? false });

    console.log('[safeRedirect] Redirección iniciada');

    return true;
};

/**
 * Limpia el flag de redirección (útil para testing o después de logout)
 */
export const clearRedirectFlag = (): void => {
    sessionStorage.removeItem(REDIRECT_FLAG_KEY);
};
