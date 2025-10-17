/**
 * Models - Tipos de modelos del dominio
 * Basados en la documentación de Features (Auth, User Management, Company Management)
 *
 * FUENTE ÚNICA DE VERDAD para tipos del sistema
 */

// ============================================
// USER & PROFILE
// ============================================

/**
 * Perfil de usuario con información personal y preferencias
 */
export interface UserProfile {
    firstName: string;
    lastName: string;
    displayName: string;
    phoneNumber: string | null;
    avatarUrl: string | null;
    createdAt: string;
    updatedAt: string;
}

/**
 * Preferencias de usuario (tema, idioma, notificaciones)
 */
export interface UserPreferences {
    theme: 'light' | 'dark';
    language: 'es' | 'en';
    timezone: string;
    pushWebNotifications: boolean;
    notificationsTickets: boolean;
    updatedAt: string;
}

/**
 * Usuario completo del sistema
 * Representa tanto usuarios finales como admins/agentes
 */
export interface User {
    id: string;
    userCode: string;
    email: string;
    emailVerified: boolean;
    status: UserStatus;
    authProvider: AuthProvider;

    // Perfil y preferencias (siempre presentes después de registro)
    profile: UserProfile;
    preferences: UserPreferences;

    // Contextos de roles (puede tener múltiples roles en diferentes empresas)
    roleContexts: RoleContext[];

    // Onboarding (null si no ha completado)
    onboardingCompletedAt: string | null;

    // Estadísticas de tickets (solo relevante para usuarios con tickets)
    ticketsCount: number;
    resolvedTicketsCount: number;
    averageRating: number | null;

    // Tracking
    lastLoginAt: string | null;
    createdAt: string;
    updatedAt: string;
}

/**
 * Estados posibles del usuario
 * - ACTIVE: Usuario activo y funcional
 * - SUSPENDED: Usuario suspendido temporalmente
 * - BANNED: Usuario baneado permanentemente
 * - PENDING: Usuario pendiente de verificación
 */
export type UserStatus = 'ACTIVE' | 'SUSPENDED' | 'BANNED' | 'PENDING';

/**
 * Proveedores de autenticación soportados
 */
export type AuthProvider = 'EMAIL' | 'GOOGLE';

// ============================================
// ROLES & PERMISSIONS
// ============================================

export type RoleCode = 'USER' | 'AGENT' | 'COMPANY_ADMIN' | 'PLATFORM_ADMIN';

export interface RoleContext {
    roleCode: RoleCode;
    roleName: string;
    company: CompanyBasicInfo | null;
    dashboardPath: string;
    assignedAt: string;
}

// ============================================
// COMPANY
// ============================================

export interface CompanyBasicInfo {
    id: string;
    companyCode: string;
    name: string;
    logoUrl: string | null;
}

export interface Company extends CompanyBasicInfo {
    email: string;
    phone: string | null;
    website: string | null;
    description: string | null;
    address: string | null;
    city: string | null;
    country: string;
    status: CompanyStatus;
    plan: CompanyPlan;
    maxAgents: number;
    currentAgents: number;
    createdAt: string;
    updatedAt: string;
}

export type CompanyStatus = 'ACTIVE' | 'SUSPENDED' | 'PENDING_APPROVAL';
export type CompanyPlan = 'FREE' | 'BASIC' | 'PRO' | 'ENTERPRISE';

// ============================================
// COMPANY REQUEST
// ============================================

export interface CompanyRequest {
    id: string;
    requestCode: string;
    companyName: string;
    adminEmail: string;
    adminFirstName: string;
    adminLastName: string;
    phone: string | null;
    website: string | null;
    description: string | null;
    address: string | null;
    city: string | null;
    country: string;
    industry: string | null;
    estimatedEmployees: number | null;
    estimatedTicketsPerMonth: number | null;
    preferredLanguage: 'es' | 'en';
    status: CompanyRequestStatus;
    reviewedBy: User | null;
    reviewNotes: string | null;
    createdAt: string;
    updatedAt: string;
}

export type CompanyRequestStatus = 'PENDING' | 'APPROVED' | 'REJECTED';

// ============================================
// AUTHENTICATION
// ============================================

export interface AuthPayload {
    accessToken: string;
    refreshToken: string;
    tokenType: string;
    expiresIn: number;
    user: User;
    roleContexts: RoleContext[];
    sessionId: string;
    loginTimestamp: string;
}

// ============================================
// SESSION
// ============================================

export interface SessionInfo {
    sessionId: string;
    deviceName: string;
    ipAddress: string;
    userAgent: string;
    lastUsedAt: string;
    expiresAt: string;
    isCurrent: boolean;
    location: {
        city: string | null;
        country: string | null;
    };
}

