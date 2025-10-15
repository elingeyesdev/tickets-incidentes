/**
 * User Management Mutations
 * Siguiendo la documentación de la API V10.1
 */

import { gql } from '@apollo/client';

// ============================================
// UPDATE MY PROFILE
// ============================================
// Retorna ProfileUpdatePayload según API V10.1

export const UPDATE_MY_PROFILE_MUTATION = gql`
    mutation UpdateMyProfile($input: UpdateProfileInput!) {
        updateMyProfile(input: $input) {
            userId
            profile {
                firstName
                lastName
                displayName
                phoneNumber
                avatarUrl
                updatedAt
            }
            updatedAt
        }
    }
`;

// ============================================
// UPDATE MY PREFERENCES
// ============================================
// Retorna PreferencesUpdatePayload según API V10.1

export const UPDATE_MY_PREFERENCES_MUTATION = gql`
    mutation UpdateMyPreferences($input: PreferencesInput!) {
        updateMyPreferences(input: $input) {
            userId
            preferences {
                theme
                language
                timezone
                pushWebNotifications
                notificationsTickets
                updatedAt
            }
            updatedAt
        }
    }
`;

// ============================================
// UPLOAD AVATAR (Si existe endpoint de upload)
// ============================================

export const UPLOAD_AVATAR_MUTATION = gql`
    mutation UploadAvatar($file: Upload!) {
        uploadAvatar(file: $file) {
            avatarUrl
        }
    }
`;

