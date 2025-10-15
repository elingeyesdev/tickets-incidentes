/**
 * Authentication Feature - Types
 * Tipos específicos para el feature de autenticación
 */

import { AuthPayload, User } from '@/types/models';

// ============================================
// FORMS
// ============================================

export interface LoginFormData {
    email: string;
    password: string;
    rememberMe?: boolean;
}

export interface RegisterFormData {
    email: string;
    password: string;
    passwordConfirmation: string;
    firstName: string;
    lastName: string;
    acceptsTerms: boolean;
    acceptsPrivacyPolicy: boolean;
}

export interface ForgotPasswordFormData {
    email: string;
}

export interface ResetPasswordFormData {
    token: string;
    email: string;
    password: string;
    passwordConfirmation: string;
}

// ============================================
// INPUTS (GraphQL)
// ============================================

export interface LoginInput {
    email: string;
    password: string;
}

export interface RegisterInput {
    email: string;
    password: string;
    passwordConfirmation: string;
    firstName: string;
    lastName: string;
    acceptsTerms: boolean;
    acceptsPrivacyPolicy: boolean;
}

export interface VerifyEmailInput {
    token: string;
}

export interface ResetPasswordInput {
    token: string;
    email: string;
    password: string;
    passwordConfirmation: string;
}

// ============================================
// RESPONSES (GraphQL)
// ============================================

export interface LoginResponse {
    login: AuthPayload;
}

export interface RegisterResponse {
    register: AuthPayload;
}

export interface VerifyEmailResponse {
    verifyEmail: {
        success: boolean;
        message: string;
        user: User;
    };
}

export interface ResendVerificationResponse {
    resendVerificationEmail: {
        success: boolean;
        message: string;
    };
}

export interface RequestPasswordResetResponse {
    requestPasswordReset: {
        success: boolean;
        message: string;
    };
}

export interface ResetPasswordResponse {
    resetPassword: {
        success: boolean;
        message: string;
    };
}

export interface LogoutResponse {
    logout: {
        success: boolean;
        message: string;
    };
}

export interface RefreshTokenResponse {
    refreshToken: {
        accessToken: string;
        tokenType: string;
        expiresIn: number;
    };
}

export interface AuthStatusResponse {
    authStatus: User | null;
}

// ============================================
// CONTEXT
// ============================================

export interface AuthContextValue {
    user: User | null;
    loading: boolean;
    login: (email: string, password: string) => Promise<void>;
    register: (data: RegisterInput) => Promise<void>;
    logout: (everywhere?: boolean) => Promise<void>;
    refreshUser: () => Promise<void>;
    canAccessRoute: (path: string) => boolean;
}

// ============================================
// VALIDATION
// ============================================

export interface ValidationErrors {
    email?: string;
    password?: string;
    passwordConfirmation?: string;
    firstName?: string;
    lastName?: string;
    acceptsTerms?: string;
    acceptsPrivacyPolicy?: string;
}

// ============================================
// EMAIL VERIFICATION
// ============================================

export interface EmailVerificationState {
    status: 'idle' | 'verifying' | 'success' | 'error';
    message: string;
    canResend: boolean;
    countdown: number;
}
