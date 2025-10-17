/**
 * Global TypeScript Definitions
 * Helpdesk System - Professional Types
 *
 * Este archivo re-exporta tipos de models.ts y define tipos específicos de Inertia.js
 * NO duplicar definiciones aquí - usar models.ts como fuente única de verdad
 */

// ============================================
// RE-EXPORTS FROM MODELS.TS
// ============================================

export type {
    User,
    UserProfile,
    UserPreferences,
    UserStatus,
    AuthProvider,
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
// RE-EXPORTS FROM GRAPHQL-GENERATED.TS
// ============================================

export * from './graphql-generated';

// ============================================
// INERTIA.JS TYPES
// ============================================

import { Config } from 'ziggy-js';
import type { User } from './models';

// ============================================
// PAGE PROPS (Inertia)
// ============================================

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User | null;
    };
    ziggy: Config;
    flash?: {
        success?: string;
        error?: string;
        info?: string;
    };
};

// ============================================
// AUTHENTICATION INPUT TYPES
// ============================================

export interface LoginInput {
    email: string;
    password: string;
    rememberMe?: boolean;
    deviceName?: string;
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

// ============================================
// GRAPHQL RESPONSE TYPES
// ============================================

export interface GraphQLError {
    message: string;
    extensions?: {
        code?: string;
        field?: string;
        [key: string]: unknown;
    };
    path?: string[];
}

export interface GraphQLResponse<T> {
    data?: T;
    errors?: GraphQLError[];
}

// ============================================
// UTILITY TYPES
// ============================================

export interface PaginatorInfo {
    count: number;
    currentPage: number;
    firstItem: number | null;
    hasMorePages: boolean;
    lastItem: number | null;
    lastPage: number;
    perPage: number;
    total: number;
}

export interface Paginated<T> {
    data: T[];
    paginatorInfo: PaginatorInfo;
}

// ============================================
// FORM TYPES
// ============================================

export interface FormErrors {
    [key: string]: string;
}

// ============================================
// MODULE AUGMENTATION (Inertia)
// ============================================

declare module '@inertiajs/react' {
    export function usePage<T = PageProps>(): {
        props: T;
        url: string;
        component: string;
        version: string | null;
    };
}

