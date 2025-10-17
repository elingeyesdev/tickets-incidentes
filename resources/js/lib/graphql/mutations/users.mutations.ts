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

