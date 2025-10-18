/**
 * GraphQL Fragments
 * Reutilizables en queries y mutations
 */

import { gql } from '@apollo/client';

// ============================================
// USER FRAGMENTS
// ============================================

export const USER_PROFILE_FRAGMENT = gql`
    fragment UserProfileFields on UserProfile {
        firstName
        lastName
        displayName
        phoneNumber
        avatarUrl
        createdAt
        updatedAt
    }
`;

export const USER_PREFERENCES_FRAGMENT = gql`
    fragment UserPreferencesFields on UserPreferences {
        theme
        language
        timezone
        pushWebNotifications
        notificationsTickets
        updatedAt
    }
`;

export const ROLE_CONTEXT_FRAGMENT = gql`
    fragment RoleContextFields on RoleContext {
        roleCode
        roleName
        company {
            id
            companyCode
            name
            logoUrl
        }
        dashboardPath
    }
`;

export const USER_FULL_FRAGMENT = gql`
    ${USER_PROFILE_FRAGMENT}
    ${ROLE_CONTEXT_FRAGMENT}

    fragment UserFullFields on User {
        id
        userCode
        email
        emailVerified
        status
        authProvider
        profile {
            ...UserProfileFields
        }
        roleContexts {
            ...RoleContextFields
        }
        ticketsCount
        resolvedTicketsCount
        averageRating
        lastLoginAt
        createdAt
        updatedAt
    }
`;

// ============================================
// AUTH FRAGMENTS
// ============================================

export const USER_AUTH_INFO_FRAGMENT = gql`
    ${ROLE_CONTEXT_FRAGMENT}

    fragment UserAuthInfoFields on UserAuthInfo {
        id
        userCode
        email
        emailVerified
        onboardingCompleted
        onboardingCompletedAt
        status
        displayName
        avatarUrl
        theme
        language
        roleContexts {
            ...RoleContextFields
        }
    }
`;

export const AUTH_PAYLOAD_FRAGMENT = gql`
    ${USER_AUTH_INFO_FRAGMENT}

    fragment AuthPayloadFields on AuthPayload {
        accessToken
        refreshToken
        tokenType
        expiresIn
        user {
            ...UserAuthInfoFields
        }
        sessionId
        loginTimestamp
    }
`;

// ============================================
// COMPANY FRAGMENTS
// ============================================

export const COMPANY_MINIMAL_FRAGMENT = gql`
    fragment CompanyMinimalFields on CompanyMinimal {
        id
        companyCode
        name
        logoUrl
    }
`;

