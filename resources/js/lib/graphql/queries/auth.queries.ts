/**
 * Authentication Queries
 */

import { gql } from '@apollo/client';
import { USER_AUTH_INFO_FRAGMENT } from '../fragments';

// ============================================
// AUTH STATUS
// ============================================

export const AUTH_STATUS_QUERY = gql`
    ${USER_AUTH_INFO_FRAGMENT}

    query AuthStatus {
        authStatus {
            isAuthenticated
            user {
                ...UserAuthInfoFields
            }
            currentSession {
                sessionId
                deviceName
                ipAddress
                lastUsedAt
                expiresAt
                isCurrent
            }
            tokenInfo {
                expiresIn
                issuedAt
                tokenType
            }
        }
    }
`;

// ============================================
// MY SESSIONS
// ============================================

export const MY_SESSIONS_QUERY = gql`
    query MySessions {
        mySessions {
            sessionId
            deviceName
            ipAddress
            userAgent
            lastUsedAt
            expiresAt
            isCurrent
            location {
                city
                country
            }
        }
    }
`;

// ============================================
// EMAIL VERIFICATION STATUS
// ============================================

export const EMAIL_VERIFICATION_STATUS_QUERY = gql`
    query EmailVerificationStatus {
        emailVerificationStatus {
            isVerified
            email
            verificationSentAt
            canResend
            resendAvailableAt
            attemptsRemaining
        }
    }
`;

