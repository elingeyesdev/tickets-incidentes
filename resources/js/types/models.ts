/**
 * Models - Tipos de modelos del dominio
 * Basados en la documentación de Features (Auth, User Management, Company Management)
 */

// ============================================
// USER & PROFILE
// ============================================

export interface UserProfile {
    firstName: string;
    lastName: string;
    displayName: string;
    phoneNumber: string | null;
    avatarUrl: string | null;
    theme: 'light' | 'dark';
    language: 'es' | 'en';
    timezone: string;
    pushWebNotifications: boolean;
    notificationsTickets: boolean;
    updatedAt: string;
}

export interface User {
    id: string;
    userCode: string;
    email: string;
    emailVerified: boolean;
    status: UserStatus;
    
    // Datos del perfil (pueden venir directamente o en el objeto profile)
    displayName?: string;
    avatarUrl?: string | null;
    theme?: 'light' | 'dark';
    language?: 'es' | 'en';
    
    // Perfil completo (opcional, puede venir más tarde)
    profile?: UserProfile;
    
    // Contextos de roles
    roleContexts: RoleContext[];
    
    createdAt: string;
    updatedAt: string;
}

export type UserStatus = 'ACTIVE' | 'SUSPENDED' | 'BANNED' | 'PENDING';

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

