import { User } from './user';

export interface RegisterData {
    email: string;
    password: string;
    confirmPassword: string;
    firstName: string;
    lastName: string;
    termsAccepted: boolean;
    privacyAccepted: boolean;
}

export interface AuthResponse {
    accessToken: string;
    refreshToken: string;
    tokenType: string;
    expiresIn: number;
    user: User;
    sessionId: string;
    loginTimestamp: string;
}
