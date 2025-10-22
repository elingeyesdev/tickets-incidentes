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
    const { isAuthenticated, loading } = useAuth();

    useEffect(() => {
        // If auth state is resolved and the user is authenticated, redirect them.
        if (!loading && isAuthenticated) {
            // Redirect to a neutral, authenticated entry point.
            // The AuthGuard on the destination route will handle the rest.
            router.visit('/dashboard', { replace: true });
        }
    }, [isAuthenticated, loading]);

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
