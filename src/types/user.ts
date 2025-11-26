export interface User {
    id: string; // UUID
    userCode: string; // USR-2025-00001
    email: string;
    status: 'ACTIVE' | 'SUSPENDED' | 'DELETED';
    emailVerified: boolean;
    emailVerifiedAt: string | null;
    lastLoginAt: string | null;
    createdAt: string;
    profile: {
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
    };

    roleContexts: Array<{
        roleCode: 'USER';
        roleName: string;
        dashboardPath: string;
        company: null;
    }>;

    ticketsCount: number;
    resolvedTicketsCount: number;
}
