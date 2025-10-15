/**
 * Global TypeScript Definitions
 * Helpdesk System - Professional Types
 */

// ============================================
// INERTIA.JS TYPES
// ============================================

import { Config } from 'ziggy-js';

export interface User {
    id: string;
    userCode: string;
    email: string;
    emailVerified: boolean;
    status: UserStatus;
    authProvider: AuthProvider;
    profile: UserProfile;
    preferences: UserPreferences;
    roleContexts: RoleContext[];
    ticketsCount: number;
    resolvedTicketsCount: number;
    averageRating: number | null;
    lastLoginAt: string | null;
    createdAt: string;
    updatedAt: string;
}

export interface UserProfile {
    firstName: string;
    lastName: string;
    displayName: string;
    phoneNumber: string | null;
    avatarUrl: string | null;
    createdAt: string;
    updatedAt: string;
}

export interface UserPreferences {
    theme: 'light' | 'dark';
    language: 'es' | 'en';
    timezone: string;
    pushWebNotifications: boolean;
    notificationsTickets: boolean;
    updatedAt: string;
}

export interface RoleContext {
    roleCode: RoleCode;
    roleName: string;
    company: RoleCompanyContext | null;
    dashboardPath: string;
}

export interface RoleCompanyContext {
    id: string;
    companyCode: string;
    name: string;
    logoUrl: string | null;
}

// ============================================
// ENUMS
// ============================================

export type UserStatus = 'ACTIVE' | 'SUSPENDED' | 'DELETED';
export type AuthProvider = 'EMAIL' | 'GOOGLE';
export type RoleCode = 'USER' | 'AGENT' | 'COMPANY_ADMIN' | 'PLATFORM_ADMIN';

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
// AUTHENTICATION
// ============================================

export interface AuthPayload {
    accessToken: string;
    refreshToken: string;
    tokenType: string;
    expiresIn: number;
    user: UserAuthInfo;
    roleContexts: RoleContext[];
    sessionId: string;
    loginTimestamp: string;
}

export interface UserAuthInfo {
    id: string;
    userCode: string;
    email: string;
    emailVerified: boolean;
    status: UserStatus;
    displayName: string;
    avatarUrl: string | null;
    theme: 'light' | 'dark';
    language: 'es' | 'en';
}

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

