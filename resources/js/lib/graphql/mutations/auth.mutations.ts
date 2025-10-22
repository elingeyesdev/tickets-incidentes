/**
 * Authentication Mutations
 */

import { gql } from '@apollo/client';
import { AUTH_PAYLOAD_FRAGMENT } from '../fragments';

// ============================================
// REGISTER
// ============================================

export const REGISTER_MUTATION = gql`
    mutation Register($input: RegisterInput!) {
        register(input: $input) {
            accessToken
            expiresIn
            user {
                id
                email
                onboardingCompletedAt
                roleContexts {
                    roleCode
                    roleName
                    dashboardPath
                }
            }
        }
    }
`;

// ============================================
// LOGIN
// ============================================

export const LOGIN_MUTATION = gql`
    mutation Login($input: LoginInput!) {
        login(input: $input) {
            accessToken
            expiresIn
            user {
                id
                email
                onboardingCompletedAt
                roleContexts {
                    roleCode
                    roleName
                    dashboardPath
                }
            }
        }
    }
`;

// ============================================
// LOGIN WITH GOOGLE
// ============================================

export const LOGIN_WITH_GOOGLE_MUTATION = gql`
    ${AUTH_PAYLOAD_FRAGMENT}

    mutation LoginWithGoogle($input: GoogleLoginInput!) {
        loginWithGoogle(input: $input) {
            ...AuthPayloadFields
        }
    }
`;

// ============================================
// LOGOUT
// ============================================

export const LOGOUT_MUTATION = gql`
    mutation Logout($everywhere: Boolean) {
        logout(everywhere: $everywhere)
    }
`;

// ============================================
// REFRESH TOKEN
// ============================================

export const REFRESH_TOKEN_MUTATION = gql`
    mutation RefreshToken {
        refreshToken {
            accessToken
            refreshToken
            tokenType
            expiresIn
        }
    }
`;

// ============================================
// VERIFY EMAIL
// ============================================

export const VERIFY_EMAIL_MUTATION = gql`
    mutation VerifyEmail($token: String!) {
        verifyEmail(token: $token) {
            success
            message
            canResend
            resendAvailableAt
        }
    }
`;

// ============================================
// RESEND VERIFICATION
// ============================================

export const RESEND_VERIFICATION_MUTATION = gql`
    mutation ResendVerification {
        resendVerification {
            success
            message
            canResend
            resendAvailableAt
        }
    }
`;

// ============================================
// RESET PASSWORD
// ============================================

export const RESET_PASSWORD_MUTATION = gql`
    mutation ResetPassword($email: Email!) {
        resetPassword(email: $email)
    }
`;

// ============================================
// CONFIRM PASSWORD RESET
// ============================================

export const CONFIRM_PASSWORD_RESET_MUTATION = gql`
    mutation ConfirmPasswordReset($input: PasswordResetInput!) {
        confirmPasswordReset(input: $input) {
            success
            message
            user {
                id
                email
                displayName
            }
        }
    }
`;

// ============================================
// MARK ONBOARDING COMPLETED
// ============================================

export const MARK_ONBOARDING_COMPLETED_MUTATION = gql`
    mutation MarkOnboardingCompleted {
        markOnboardingCompleted {
            success
            message
            user {
                id
                userCode
                email
                emailVerified
                onboardingCompleted
                displayName
                avatarUrl
                theme
                language
                roleContexts {
                    roleCode
                    roleName
                    company {
                        id
                        name
                    }
                    dashboardPath
                }
            }
        }
    }
`;

