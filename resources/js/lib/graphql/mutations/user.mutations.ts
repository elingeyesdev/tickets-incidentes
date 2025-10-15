/**
 * User Management Mutations (usando Fragments)
 * Siguiendo la documentación de la API V10.1
 * 
 * NOTA: Este archivo está deprecado. Usar mutations/users.mutations.ts
 * Se mantiene por compatibilidad con código existente.
 */

import { gql } from '@apollo/client';
import { USER_PROFILE_FRAGMENT, USER_PREFERENCES_FRAGMENT } from '../fragments';

// ============================================
// UPDATE MY PROFILE
// ============================================
// Retorna ProfileUpdatePayload según API V10.1

export const UPDATE_MY_PROFILE_MUTATION = gql`
    ${USER_PROFILE_FRAGMENT}

    mutation UpdateMyProfile($input: UpdateProfileInput!) {
        updateMyProfile(input: $input) {
            userId
            profile {
                ...UserProfileFields
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
    ${USER_PREFERENCES_FRAGMENT}

    mutation UpdateMyPreferences($input: PreferencesInput!) {
        updateMyPreferences(input: $input) {
            userId
            preferences {
                ...UserPreferencesFields
            }
            updatedAt
        }
    }
`;

