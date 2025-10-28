/**
 * OnboardingRoute Guard
 *
 * Protects onboarding routes:
 * - User must be authenticated
 * - User must NOT have completed onboarding yet
 * - Email must be verified
 * If any condition fails, redirect appropriately.
 */

import React, { useEffect } from 'react';
import { router } from '@inertiajs/react';
import { useAuth } from '@/contexts';
import FullscreenLoader from '@/components/Shared/FullscreenLoader';

interface OnboardingRouteProps {
    children: React.ReactNode;
}

export function OnboardingRoute({ children }: OnboardingRouteProps) {
    const { isAuthenticated, loading, hasCompletedOnboarding, user } = useAuth();

    useEffect(() => {
        if (loading) return;

        // 1. Not authenticated - go to login
        if (!isAuthenticated) {
            router.visit('/login', { replace: true });
            return;
        }

        // 2. Email not verified - go to verify email
        if (user && !user.emailVerified) {
            router.visit('/verify-email', { replace: true });
            return;
        }

        // 3. Already completed onboarding - go to dashboard
        if (hasCompletedOnboarding()) {
            if (user?.roleContexts && user.roleContexts.length > 1) {
                router.visit('/role-selector', { replace: true });
            } else if (user?.roleContexts && user.roleContexts.length === 1) {
                // dashboardPath is a URL (e.g., '/dashboard', '/agent/dashboard')
                router.visit(user.roleContexts[0].dashboardPath || '/dashboard', { replace: true });
            } else {
                router.visit('/dashboard', { replace: true });
            }
        }
    }, [isAuthenticated, loading, hasCompletedOnboarding, user]);

    // While loading or redirecting, show a loader
    if (loading) {
        return <FullscreenLoader message="Verificando..." />;
    }

    // Not authenticated
    if (!isAuthenticated) {
        return <FullscreenLoader message="Redirigiendo..." />;
    }

    // Render the onboarding page
    return <>{children}</>;
}
