import React, { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { useAuth } from '@/contexts';
import FullscreenLoader from '@/components/Shared/FullscreenLoader';
import { RoleCode } from '@/types';

interface AuthGuardProps {
    children: React.ReactNode;
    allowedRoles?: RoleCode[];
}

/**
 * AuthGuard - The Single Source of Truth for Protecting Private Routes.
 *
 * This component centralizes all authorization logic:
 * 1. Verifies the user is authenticated.
 * 2. Verifies onboarding is complete.
 * 3. Verifies the user has selected a role if they have multiple.
 * 4. Verifies the selected role is allowed to access the current page.
 */
export const AuthGuard: React.FC<AuthGuardProps> = ({ children, allowedRoles = [] }) => {
    const {
        user,
        isAuthenticated,
        loading: authLoading,
        hasCompletedOnboarding,
        lastSelectedRole,
    } = useAuth();

    const [isVerifying, setIsVerifying] = useState(true);

    useEffect(() => {
        if (authLoading) return;

        if (!isAuthenticated) {
            router.visit('/login', { replace: true });
            return;
        }

        if (user) {
            // 1. Email Verification Check (MUST BE FIRST)
            if (!user.emailVerified) {
                router.visit('/verify-email', { replace: true });
                return;
            }

            // 2. Onboarding Check
            if (!hasCompletedOnboarding()) {
                router.visit('/onboarding/profile', { replace: true });
                return;
            }

            // 3. Role Selection Check for Multi-Role users
            const multiRole = user.roleContexts && user.roleContexts.length > 1;
            if (multiRole && !lastSelectedRole) {
                router.visit('/role-selector', { replace: true });
                return;
            }

            // 4. Role Permission Check
            if (allowedRoles.length > 0 && lastSelectedRole) {
                if (!allowedRoles.includes(lastSelectedRole as RoleCode)) {
                    router.visit('/unauthorized', { replace: true });
                    return;
                }
            }
        }

        setIsVerifying(false);

    }, [authLoading, isAuthenticated, user, hasCompletedOnboarding, lastSelectedRole, allowedRoles]);

    if (authLoading || isVerifying) {
        return <FullscreenLoader message="Verificando acceso..." />;
    }

    return <>{children}</>;
};