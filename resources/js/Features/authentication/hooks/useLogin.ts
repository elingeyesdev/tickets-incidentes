/**
 * useLogin Hook
 * Business logic for user login
 *
 * Uses TokenManager for centralized token management
 * Broadcasts login events via AuthChannel for multi-tab sync
 */

import { useState, useEffect, FormEvent } from 'react';
import { useMutation } from '@apollo/client/react';
import { LOGIN_MUTATION } from '@/lib/graphql/mutations/auth.mutations';
import { TokenManager } from '@/lib/auth/TokenManager';
import { AuthChannel } from '@/lib/auth/AuthChannel';
import { useNotification } from '@/contexts';
import type { LoginInput, LoginMutation, LoginMutationVariables, RoleContext } from '@/types/graphql';
import { router } from '@inertiajs/react';

interface UseLoginOptions {
    onSuccess?: () => void;
    onError?: (error: Error) => void;
}

export const useLogin = (options?: UseLoginOptions) => {
    const { error: showError } = useNotification();

    const [formData, setFormData] = useState<LoginInput>({
        email: '',
        password: '',
        rememberMe: false,
        deviceName: navigator.userAgent.split(' ').slice(0, 2).join(' ') || 'Unknown Device',
    });

    const [showPassword, setShowPassword] = useState(false);
    const [touched, setTouched] = useState({
        email: false,
        password: false,
    });

    // Validaciones en tiempo real
    const [validation, setValidation] = useState({
        email: { valid: false, message: '' },
        password: { valid: false, message: '' },
    });

    // Actualizar validaciones cuando cambian los campos
    useEffect(() => {
        setValidation({
            email: validateEmail(formData.email),
            password: validatePassword(formData.password),
        });
    }, [formData.email, formData.password]);

    const [login, { loading, error }] = useMutation<LoginMutation, LoginMutationVariables>(LOGIN_MUTATION, {
        onCompleted: (data) => {
            const { accessToken, expiresIn, user } = data.login;
            const roleContexts = user.roleContexts;

            console.log('✅ useLogin: Login successful', {
                email: user.email,
                roles: roleContexts.map((rc: RoleContext) => rc.roleCode),
                onboardingCompleted: user.onboardingCompletedAt
            });

            // Use TokenManager to store token (single source of truth)
            TokenManager.setToken(accessToken, expiresIn, user, roleContexts);

            // Broadcast login event to other tabs for multi-tab sync
            AuthChannel.broadcast({
                type: 'LOGIN',
                payload: { userId: user.id, timestamp: Date.now() }
            });

            // Callback de éxito
            if (options?.onSuccess) {
                options.onSuccess();
                return;
            }

            // Determine redirect path based on user state
            let redirectPath: string;

            // Priority 1: Email verification (must be first)
            if (!user.emailVerified) {
                redirectPath = '/verify-email';
                console.log('🔄 useLogin: Redirecting to email verification');
            }
            // Priority 2: Onboarding completion
            else if (!user.onboardingCompletedAt) {
                redirectPath = '/onboarding/profile';
                console.log('🔄 useLogin: Redirecting to onboarding');
            }
            // Priority 3: Role selection (if multiple roles)
            else if (roleContexts.length === 1) {
                // User with single role → go directly to their dashboard
                redirectPath = roleContexts[0].dashboardPath;
                console.log('🔄 useLogin: Redirecting to single dashboard', redirectPath);
            } else {
                // User with multiple roles → go to role selector
                redirectPath = '/role-selector';
                console.log('🔄 useLogin: Redirecting to role selector');
            }

            // Use Inertia router for smooth navigation
            router.visit(redirectPath);
        },
        onError: (err) => {
            const errorMessage = err.message || 'Error al iniciar sesión';
            showError(errorMessage);

            if (options?.onError) {
                options.onError(new Error(errorMessage));
            }
        },
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        // Marcar todos como touched
        setTouched({ email: true, password: true });

        // Validar
        if (!validation.email.valid || !validation.password.valid) {
            showError('Por favor, corrige los errores en el formulario');
            return;
        }

        login({ variables: { input: formData } });
    };

    const handleGoogleLogin = () => {
        // TODO: Implementar Google OAuth
        showError('Función de Google en desarrollo');
    };

    const isFormValid = validation.email.valid && validation.password.valid;

    return {
        formData,
        setFormData,
        showPassword,
        setShowPassword,
        touched,
        setTouched,
        validation,
        loading,
        error,
        isFormValid,
        handleSubmit,
        handleGoogleLogin,
    };
};

// ============================================
// VALIDATION HELPERS
// ============================================

const validateEmail = (email: string): { valid: boolean; message: string } => {
    if (!email) return { valid: false, message: '' };
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        return { valid: false, message: 'Email inválido' };
    }
    return { valid: true, message: 'Email válido' };
};

const validatePassword = (password: string): { valid: boolean; message: string } => {
    if (!password) return { valid: false, message: '' };
    if (password.length < 8) {
        return { valid: false, message: 'Mínimo 8 caracteres' };
    }
    return { valid: true, message: 'Contraseña válida' };
};
