/**
 * PublicRoute Guard
 *
 * This component protects public routes like /login and /register.
 * Its single responsibility is to redirect authenticated users away from public pages.
 */

import React, { useEffect } from 'react';
import { router } from '@inertiajs/react';
import { useAuth } from '@/contexts'; // Use the correct hook from contexts
import FullscreenLoader from '@/components/Shared/FullscreenLoader';

interface PublicRouteProps {
    children: React.ReactNode;
}

export function PublicRoute({ children }: PublicRouteProps) {
    const { isAuthenticated, loading, hasCompletedOnboarding, user } = useAuth();

    useEffect(() => {
        // If auth state is resolved and the user is authenticated, redirect them.
        if (!loading && isAuthenticated && user) {
            // Determinar ruta según estado del usuario
            let redirectPath = '/dashboard';
            
            if (!hasCompletedOnboarding()) {
                redirectPath = '/onboarding/profile';
            } else if (user.roleContexts && user.roleContexts.length > 1) {
                redirectPath = '/role-selector';
            } else if (user.roleContexts && user.roleContexts.length === 1) {
                redirectPath = user.roleContexts[0].dashboardPath || '/dashboard';
            }
            
            router.visit(redirectPath, { replace: true });
        }
    }, [isAuthenticated, loading, hasCompletedOnboarding, user]);

    // While authentication is loading, show a loader to prevent content flashing.
    if (loading) {
        return <FullscreenLoader message="Verificando..." />;
    }

    // If the user is authenticated, a redirect is in progress.
    // Show a loader to provide feedback during the brief redirection period.
    if (isAuthenticated) {
        return <FullscreenLoader message="Redirigiendo..." />;
    }

    // If not loading and not authenticated, render the public page.
    return <>{children}</>;
}
