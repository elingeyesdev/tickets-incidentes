import { create } from 'zustand';
import { User } from '../types/user';
import { client } from '../services/api/client';
import { useAuthStore } from './authStore';

interface UserState {
    updateProfile: (data: Partial<Pick<User, 'firstName' | 'lastName' | 'displayName' | 'phoneNumber' | 'avatarUrl'>>) => Promise<void>;
    updatePreferences: (data: Partial<Pick<User, 'theme' | 'language' | 'timezone' | 'pushWebNotifications' | 'notificationsTickets'>>) => Promise<void>;
    fetchSessions: () => Promise<Session[]>;
    revokeSession: (sessionId: string) => Promise<void>;
    revokeAllOtherSessions: () => Promise<void>;
}

export interface Location {
    city: string | null;
    country: string | null;
    country_code: string | null;
    latitude: number | null;
    longitude: number | null;
    timezone: string | null;
}

export interface Session {
    id: string;
    deviceName: string | null;
    ipAddress: string | null;
    userAgent: string | null;
    lastUsedAt: string;
    expiresAt: string;
    isCurrent: boolean;
    location: Location | null;
}

export const useUserStore = create<UserState>((set, get) => ({
    updateProfile: async (data) => {
        const response = await client.patch('/api/users/me/profile', data);
        // Update auth store user
        const currentUser = useAuthStore.getState().user;
        if (currentUser) {
            useAuthStore.setState({
                user: {
                    ...currentUser,
                    ...data,
                },
            });
        }
    },

    updatePreferences: async (data) => {
        await client.patch('/api/users/me/preferences', data);
        const currentUser = useAuthStore.getState().user;
        if (currentUser) {
            useAuthStore.setState({
                user: {
                    ...currentUser,
                    ...data,
                },
            });
        }
    },

    fetchSessions: async () => {
        const response = await client.get('/api/auth/sessions');
        const sessions = response.data.sessions || [];
        return sessions.map((session: any) => ({
            id: session.sessionId,
            deviceName: session.deviceName,
            ipAddress: session.ipAddress,
            userAgent: session.userAgent,
            lastUsedAt: session.lastUsedAt,
            expiresAt: session.expiresAt,
            isCurrent: session.isCurrent,
            location: session.location,
        }));
    },

    revokeSession: async (sessionId) => {
        await client.delete(`/api/auth/sessions/${sessionId}`);
    },

    revokeAllOtherSessions: async () => {
        // Revoke all sessions except current one
        // Since backend doesn't have a specific endpoint for "all others",
        // we fetch sessions and revoke each non-current one individually
        const sessions = await get().fetchSessions();
        const nonCurrentSessions = sessions.filter(s => !s.isCurrent);

        // Revoke each session sequentially
        for (const session of nonCurrentSessions) {
            try {
                await get().revokeSession(session.id);
            } catch (error) {
                console.error(`Failed to revoke session ${session.id}:`, error);
                // Continue with other sessions even if one fails
            }
        }
    }
}));
