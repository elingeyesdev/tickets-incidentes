/**
 * useLogin Hook
 * Lógica de negocio para el login de usuarios
 */

import { useState, useEffect, FormEvent } from 'react';
import { useMutation } from '@apollo/client/react';
import { LOGIN_MUTATION } from '@/lib/graphql/mutations/auth.mutations';
import { saveAuthTokens, saveUserData } from '@/lib/apollo/client';
import { useNotification } from '@/contexts';
import type { LoginInput } from '../types';

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

    const [login, { loading, error }] = useMutation(LOGIN_MUTATION, {
        onCompleted: (data: any) => {
            const { accessToken, expiresIn, user } = data.login;
            const roleContexts = user.roleContexts; // Ahora roleContexts está dentro de user

            console.log('✅ useLogin: Login exitoso', {
                email: user.email,
                roles: roleContexts.map((rc: any) => rc.roleCode),
                onboardingCompleted: user.onboardingCompletedAt
            });

            // Guardar tokens (refresh token ya está en httpOnly cookie)
            saveAuthTokens(accessToken, expiresIn);

            // Guardar usuario y roleContexts temporalmente para que AuthContext los pueda leer
            saveUserData(user, roleContexts);

            // Callback de éxito
            if (options?.onSuccess) {
                options.onSuccess();
                return;
            }

            // Determinar redirección según estado del onboarding
            let redirectPath: string;

            if (!user.onboardingCompletedAt) {
                // Usuario sin onboarding completo → ir a onboarding
                redirectPath = '/onboarding/profile';
                console.log('🔄 useLogin: Redirigiendo a onboarding');
            } else if (roleContexts.length === 1) {
                // Usuario con 1 rol → ir directo a su dashboard
                redirectPath = roleContexts[0].dashboardPath;
                console.log('🔄 useLogin: Redirigiendo a dashboard único', redirectPath);
            } else {
                // Usuario con múltiples roles → ir a role selector
                redirectPath = '/role-selector';
                console.log('🔄 useLogin: Redirigiendo a role selector');
            }

            // Usar router.visit para redirección suave sin recargar página
            router.visit(redirectPath);
        },
        onError: (err: any) => {
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
