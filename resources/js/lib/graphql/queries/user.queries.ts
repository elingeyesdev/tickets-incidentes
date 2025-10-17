/**
 * User Management Queries
 */

import { gql } from '@apollo/client';
import {
    USER_FULL_FRAGMENT,
    USER_PROFILE_FRAGMENT,
} from '../fragments';

// ============================================
// ME - Usuario Autenticado Completo
// ============================================

export const ME_QUERY = gql`
    ${USER_FULL_FRAGMENT}

    query Me {
        me {
            ...UserFullFields
        }
    }
`;

// ============================================
// MY PROFILE - Solo Perfil
// ============================================

export const MY_PROFILE_QUERY = gql`
    ${USER_PROFILE_FRAGMENT}

    query MyProfile {
        myProfile {
            ...UserProfileFields
        }
    }
`;

