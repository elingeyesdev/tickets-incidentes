import React, { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { useAuth } from '@/contexts';
import FullscreenLoader from '@/components/Shared/FullscreenLoader';

interface AuthGuardProps {
    children: React.ReactNode;
}

/**
 * AuthGuard
 *
 * Este componente actúa como un guardián para las rutas protegidas. Se asegura de que
 * el usuario esté autenticado y haya completado todos los pasos preliminares necesarios
 * (como el onboarding y la selección de rol) antes de permitir el acceso a una página.
 *
 * Se ejecuta en cada carga de página dentro de un layout protegido.
 */
export const AuthGuard: React.FC<AuthGuardProps> = ({ children }) => {
    const {
        user,
        isAuthenticated,
        loading: authLoading,
        hasCompletedOnboarding,
    } = useAuth();

    // Estado interno para gestionar la lógica de redirección y evitar flashes de contenido
    const [isVerifying, setIsVerifying] = useState(true);

    useEffect(() => {
        // No ejecutar las verificaciones hasta que el estado de autenticación principal esté resuelto
        if (authLoading) {
            return;
        }

        // Si no está autenticado, redirigir a login inmediatamente.
        if (!isAuthenticated) {
            router.visit('/login', { replace: true });
            return; // Detener la ejecución aquí
        }

        // En este punto, el usuario está autenticado. Ahora, ejecutar las verificaciones secuenciales.
        if (user) {
            // 1. Verificar si ha completado el onboarding.
            if (!hasCompletedOnboarding()) {
                router.visit('/onboarding/profile', { replace: true });
                return; // Detener la ejecución aquí
            }

            // 2. Verificar la selección de rol para usuarios con múltiples roles.
            const multiRole = user.roleContexts && user.roleContexts.length > 1;
            
            // TODO: El `lastSelectedRole` necesita ser expuesto desde `useAuth`.
            // Por ahora, asumimos que está en el objeto `user` para implementar la lógica.
            const lastSelectedRole = (user as any).lastSelectedRole;

            if (multiRole && !lastSelectedRole) {
                router.visit('/role-selector', { replace: true });
                return; // Detener la ejecución aquí
            }
        }

        // Si todas las verificaciones pasan, detener el estado de verificación y permitir que el contenido se renderice.
        setIsVerifying(false);

    }, [authLoading, isAuthenticated, user, hasCompletedOnboarding]);

    // Mientras el AuthContext está cargando o mientras estamos verificando, mostrar un loader.
    // Esto previene cualquier "flash" del contenido protegido.
    if (authLoading || isVerifying) {
        return <FullscreenLoader message="Verificando sesión..." />;
    }

    // Todas las verificaciones pasaron, renderizar el contenido protegido.
    return <>{children}</>;
};