/**
 * GraphQL Types - DEPRECATED
 * Este archivo está siendo reemplazado por types/models.ts y Features/{feature}/types.ts
 * Por favor, usa los nuevos tipos en su lugar.
 * @deprecated
 */

// ============================================
// RE-EXPORTS para compatibilidad
// ============================================

// Authentication Feature
export type {
    LoginInput,
    RegisterInput,
    LoginResponse,
    RegisterResponse,
    VerifyEmailInput,
    VerifyEmailResponse,
    ResendVerificationResponse,
    LogoutResponse,
    RefreshTokenResponse,
    AuthStatusResponse,
} from '@/Features/authentication/types';

// Global Models
export type {
    User,
    UserProfile,
    UserStatus,
    RoleCode,
    RoleContext,
    Company,
    CompanyBasicInfo,
    CompanyStatus,
    CompanyPlan,
    CompanyRequest,
    CompanyRequestStatus,
    AuthPayload,
    SessionInfo,
} from './models';

// ============================================
// MUTATION VARIABLES (Legacy - for existing code)
// ============================================

export interface LoginMutationVariables {
    input: {
        email: string;
        password: string;
        rememberMe?: boolean;
        deviceName?: string;
    };
}

export interface RegisterMutationVariables {
    input: {
        email: string;
        password: string;
        passwordConfirmation: string;
        firstName: string;
        lastName: string;
        acceptsTerms: boolean;
        acceptsPrivacyPolicy: boolean;
    };
}

export interface UpdateProfileMutationVariables {
    input: {
        firstName?: string;
        lastName?: string;
        phoneNumber?: string;
        avatarUrl?: string;
    };
}

export interface UpdatePreferencesMutationVariables {
    input: {
        theme?: 'light' | 'dark';
        language?: 'es' | 'en';
        timezone?: string;
        pushWebNotifications?: boolean;
        notificationsTickets?: boolean;
    };
}

// ============================================
// QUERY RESULTS (Legacy)
// ============================================

export interface MeQueryResult {
    me: {
        id: string;
        userCode: string;
        email: string;
        emailVerified: boolean;
        status: string;
        profile: {
            firstName: string;
            lastName: string;
            displayName: string;
            phoneNumber: string | null;
            avatarUrl: string | null;
        };
        preferences: {
            theme: 'light' | 'dark';
            language: 'es' | 'en';
            timezone: string;
            pushWebNotifications: boolean;
            notificationsTickets: boolean;
        };
        roleContexts: Array<{
            roleCode: string;
            roleName: string;
            company: {
                id: string;
                companyCode: string;
                name: string;
                logoUrl: string | null;
            } | null;
            dashboardPath: string;
        }>;
    };
}
