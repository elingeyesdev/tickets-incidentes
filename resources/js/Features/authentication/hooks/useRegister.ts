/**
 * useRegister Hook
 * Business logic for user registration
 *
 * Uses TokenManager for centralized token management
 * Broadcasts registration events via AuthChannel for multi-tab sync
 */

import { useState, useEffect, FormEvent } from 'react';
import { useMutation } from '@apollo/client/react';
import { REGISTER_MUTATION } from '@/lib/graphql/mutations/auth.mutations';
import { TokenManager } from '@/lib/auth/TokenManager';
import { AuthChannel } from '@/lib/auth/AuthChannel';
import { useNotification } from '@/contexts';
import type { RegisterInput } from '../types';
import { router } from '@inertiajs/react';
import type { RegisterMutation, RegisterMutationVariables, RoleContext } from '@/types/graphql';

interface UseRegisterOptions {
    onSuccess?: () => void;
    onError?: (error: Error) => void;
}

export const useRegister = (options?: UseRegisterOptions) => {
    const { error: showError } = useNotification();

    const [formData, setFormData] = useState<RegisterInput>({
        email: '',
        password: '',
        passwordConfirmation: '',
        firstName: '',
        lastName: '',
        acceptsTerms: false,
        acceptsPrivacyPolicy: false,
    });

    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);
    const [touched, setTouched] = useState({
        email: false,
        password: false,
        passwordConfirmation: false,
        firstName: false,
        lastName: false,
    });

    // Validaciones en tiempo real
    const [validation, setValidation] = useState({
        email: { valid: false, message: '' },
        password: { valid: false, message: '', strength: 0 },
        passwordConfirmation: { valid: false, message: '' },
        firstName: { valid: false, message: '' },
        lastName: { valid: false, message: '' },
    });

    // Actualizar validaciones cuando cambian los campos
    useEffect(() => {
        setValidation({
            email: validateEmail(formData.email),
            password: validatePassword(formData.password),
            passwordConfirmation: validatePasswordConfirmation(
                formData.password,
                formData.passwordConfirmation
            ),
            firstName: validateName(formData.firstName, 'Nombre'),
            lastName: validateName(formData.lastName, 'Apellido'),
        });
    }, [
        formData.email,
        formData.password,
        formData.passwordConfirmation,
        formData.firstName,
        formData.lastName,
    ]);

    const [register, { loading, error }] = useMutation<RegisterMutation, RegisterMutationVariables>(REGISTER_MUTATION, {
        onCompleted: (data) => {
            const { accessToken, expiresIn, user } = data.register;
            const roleContexts = user.roleContexts;

            console.log('✅ useRegister: Registration successful', {
                email: user.email,
                roles: roleContexts.map((rc: RoleContext) => rc.roleCode),
            });

            // 1. IMPORTANT: Clear any existing session before setting the new one.
            TokenManager.clearToken();

            // 2. Use TokenManager to store the new token (single source of truth)
            TokenManager.setToken(accessToken, expiresIn, user, roleContexts);

            // 3. Broadcast registration event to other tabs for multi-tab sync
            AuthChannel.broadcast({
                type: 'LOGIN',
                payload: { userId: user.id, timestamp: Date.now() }
            });

            // Callback de éxito
            if (options?.onSuccess) {
                options.onSuccess();
                return;
            }

            // 4. Redirect to email verification using Inertia router
            router.visit('/verify-email');
        },
        onError: (err) => {
            const errorMessage = err.message || 'Error al registrar usuario';
            showError(errorMessage);

            if (options?.onError) {
                options.onError(new Error(errorMessage));
            }
        },
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        // Marcar todos como touched
        setTouched({
            email: true,
            password: true,
            passwordConfirmation: true,
            firstName: true,
            lastName: true,
        });

        // Validar
        if (
            !validation.email.valid ||
            !validation.password.valid ||
            !validation.passwordConfirmation.valid ||
            !validation.firstName.valid ||
            !validation.lastName.valid
        ) {
            showError('Por favor, corrige los errores en el formulario');
            return;
        }

        // Validar términos y política
        if (!formData.acceptsTerms || !formData.acceptsPrivacyPolicy) {
            showError('Debes aceptar los términos y política de privacidad');
            return;
        }

        register({ variables: { input: formData } });
    };

    const handleGoogleRegister = () => {
        // TODO: Implementar Google OAuth
        showError('Función de Google en desarrollo');
    };

    const isFormValid =
        validation.email.valid &&
        validation.password.valid &&
        validation.passwordConfirmation.valid &&
        validation.firstName.valid &&
        validation.lastName.valid &&
        formData.acceptsTerms &&
        formData.acceptsPrivacyPolicy;

    return {
        formData,
        setFormData,
        showPassword,
        setShowPassword,
        showPasswordConfirmation,
        setShowPasswordConfirmation,
        touched,
        setTouched,
        validation,
        loading,
        error,
        isFormValid,
        handleSubmit,
        handleGoogleRegister,
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

const validatePassword = (password: string): { valid: boolean; message: string; strength: number } => {
    if (!password) return { valid: false, message: '', strength: 0 };

    let strength = 0;
    const checks = {
        length: password.length >= 8,
        lowercase: /[a-z]/.test(password),
        uppercase: /[A-Z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[!@#$%^&*(),.?":{}|<>]/.test(password),
    };

    if (checks.length) strength++;
    if (checks.lowercase) strength++;
    if (checks.uppercase) strength++;
    if (checks.number) strength++;
    if (checks.special) strength++;

    if (!checks.length) {
        return { valid: false, message: 'Mínimo 8 caracteres', strength: 0 };
    }

    if (strength < 3) {
        return { valid: false, message: 'Contraseña débil', strength };
    }

    return {
        valid: true,
        message: strength >= 4 ? 'Contraseña fuerte' : 'Contraseña aceptable',
        strength,
    };
};

const validatePasswordConfirmation = (
    password: string,
    confirmation: string
): { valid: boolean; message: string } => {
    if (!confirmation) return { valid: false, message: '' };
    if (password !== confirmation) {
        return { valid: false, message: 'Las contraseñas no coinciden' };
    }
    return { valid: true, message: 'Las contraseñas coinciden' };
};

const validateName = (name: string, fieldName: string): { valid: boolean; message: string } => {
    if (!name) return { valid: false, message: '' };
    if (name.length < 2) {
        return { valid: false, message: `${fieldName} muy corto (mínimo 2 caracteres)` };
    }
    if (name.length > 100) {
        return { valid: false, message: `${fieldName} muy largo (máximo 100 caracteres)` };
    }
    return { valid: true, message: `${fieldName} válido` };
};

