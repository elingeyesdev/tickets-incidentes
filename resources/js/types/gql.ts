/* eslint-disable */
import * as types from './graphql';
import { TypedDocumentNode as DocumentNode } from '@graphql-typed-document-node/core';

/**
 * Map of all GraphQL operations in the project.
 *
 * This map has several performance disadvantages:
 * 1. It is not tree-shakeable, so it will include all operations in the project.
 * 2. It is not minifiable, so the string of a GraphQL query will be multiple times inside the bundle.
 * 3. It does not support dead code elimination, so it will add unused operations.
 *
 * Therefore it is highly recommended to use the babel or swc plugin for production.
 * Learn more about it here: https://the-guild.dev/graphql/codegen/plugins/presets/preset-client#reducing-bundle-size
 */
type Documents = {
    "\n    query Heartbeat {\n        authStatus {\n            isAuthenticated\n        }\n    }\n": typeof types.HeartbeatDocument,
    "\n    fragment UserProfileFields on UserProfile {\n        firstName\n        lastName\n        displayName\n        phoneNumber\n        avatarUrl\n        createdAt\n        updatedAt\n    }\n": typeof types.UserProfileFieldsFragmentDoc,
    "\n    fragment UserPreferencesFields on UserPreferences {\n        theme\n        language\n        timezone\n        pushWebNotifications\n        notificationsTickets\n        updatedAt\n    }\n": typeof types.UserPreferencesFieldsFragmentDoc,
    "\n    fragment RoleContextFields on RoleContext {\n        roleCode\n        roleName\n        company {\n            id\n            companyCode\n            name\n            logoUrl\n        }\n        dashboardPath\n    }\n": typeof types.RoleContextFieldsFragmentDoc,
    "\n    \n    \n\n    fragment UserFullFields on User {\n        id\n        userCode\n        email\n        emailVerified\n        status\n        authProvider\n        profile {\n            ...UserProfileFields\n        }\n        roleContexts {\n            ...RoleContextFields\n        }\n        ticketsCount\n        resolvedTicketsCount\n        averageRating\n        lastLoginAt\n        createdAt\n        updatedAt\n    }\n": typeof types.UserFullFieldsFragmentDoc,
    "\n    \n\n    fragment UserAuthInfoFields on UserAuthInfo {\n        id\n        userCode\n        email\n        emailVerified\n        onboardingCompleted\n        onboardingCompletedAt\n        status\n        displayName\n        avatarUrl\n        theme\n        language\n        roleContexts {\n            ...RoleContextFields\n        }\n    }\n": typeof types.UserAuthInfoFieldsFragmentDoc,
    "\n    \n\n    fragment AuthPayloadFields on AuthPayload {\n        accessToken\n        refreshToken\n        tokenType\n        expiresIn\n        user {\n            ...UserAuthInfoFields\n        }\n        sessionId\n        loginTimestamp\n    }\n": typeof types.AuthPayloadFieldsFragmentDoc,
    "\n    fragment CompanyMinimalFields on CompanyMinimal {\n        id\n        companyCode\n        name\n        logoUrl\n    }\n": typeof types.CompanyMinimalFieldsFragmentDoc,
    "\n    mutation Register($input: RegisterInput!) {\n        register(input: $input) {\n            accessToken\n            expiresIn\n            user {\n                id\n                email\n                onboardingCompletedAt\n                roleContexts {\n                    roleCode\n                    roleName\n                    dashboardPath\n                }\n            }\n        }\n    }\n": typeof types.RegisterDocument,
    "\n    mutation Login($input: LoginInput!) {\n        login(input: $input) {\n            accessToken\n            expiresIn\n            user {\n                id\n                email\n                onboardingCompletedAt\n                roleContexts {\n                    roleCode\n                    roleName\n                    dashboardPath\n                }\n            }\n        }\n    }\n": typeof types.LoginDocument,
    "\n    \n\n    mutation LoginWithGoogle($input: GoogleLoginInput!) {\n        loginWithGoogle(input: $input) {\n            ...AuthPayloadFields\n        }\n    }\n": typeof types.LoginWithGoogleDocument,
    "\n    mutation Logout($everywhere: Boolean) {\n        logout(everywhere: $everywhere)\n    }\n": typeof types.LogoutDocument,
    "\n    mutation RefreshToken {\n        refreshToken {\n            accessToken\n            refreshToken\n            tokenType\n            expiresIn\n        }\n    }\n": typeof types.RefreshTokenDocument,
    "\n    mutation VerifyEmail($token: String!) {\n        verifyEmail(token: $token) {\n            success\n            message\n            canResend\n            resendAvailableAt\n        }\n    }\n": typeof types.VerifyEmailDocument,
    "\n    mutation ResendVerification {\n        resendVerification {\n            success\n            message\n            canResend\n            resendAvailableAt\n        }\n    }\n": typeof types.ResendVerificationDocument,
    "\n    mutation ResetPassword($email: Email!) {\n        resetPassword(email: $email)\n    }\n": typeof types.ResetPasswordDocument,
    "\n    mutation ConfirmPasswordReset($input: PasswordResetInput!) {\n        confirmPasswordReset(input: $input) {\n            success\n            message\n            user {\n                id\n                email\n                displayName\n            }\n        }\n    }\n": typeof types.ConfirmPasswordResetDocument,
    "\n    mutation MarkOnboardingCompleted {\n        markOnboardingCompleted {\n            success\n            message\n            user {\n                id\n                userCode\n                email\n                emailVerified\n                onboardingCompleted\n                displayName\n                avatarUrl\n                theme\n                language\n                roleContexts {\n                    roleCode\n                    roleName\n                    company {\n                        id\n                        name\n                    }\n                    dashboardPath\n                }\n            }\n        }\n    }\n": typeof types.MarkOnboardingCompletedDocument,
    "\n    mutation UpdateMyProfile($input: UpdateProfileInput!) {\n        updateMyProfile(input: $input) {\n            userId\n            profile {\n                firstName\n                lastName\n                displayName\n                phoneNumber\n                avatarUrl\n                updatedAt\n            }\n            updatedAt\n        }\n    }\n": typeof types.UpdateMyProfileDocument,
    "\n    \n\n    query AuthStatus {\n        authStatus {\n            isAuthenticated\n            user {\n                ...UserAuthInfoFields\n            }\n            currentSession {\n                sessionId\n                deviceName\n                ipAddress\n                lastUsedAt\n                expiresAt\n                isCurrent\n            }\n            tokenInfo {\n                expiresIn\n                issuedAt\n                tokenType\n            }\n        }\n    }\n": typeof types.AuthStatusDocument,
    "\n    query MySessions {\n        mySessions {\n            sessionId\n            deviceName\n            ipAddress\n            userAgent\n            lastUsedAt\n            expiresAt\n            isCurrent\n            location {\n                city\n                country\n            }\n        }\n    }\n": typeof types.MySessionsDocument,
    "\n    query EmailVerificationStatus {\n        emailVerificationStatus {\n            isVerified\n            email\n            verificationSentAt\n            canResend\n            resendAvailableAt\n            attemptsRemaining\n        }\n    }\n": typeof types.EmailVerificationStatusDocument,
    "\n    \n\n    query Me {\n        me {\n            ...UserFullFields\n        }\n    }\n": typeof types.MeDocument,
    "\n    \n\n    query MyProfile {\n        myProfile {\n            ...UserProfileFields\n        }\n    }\n": typeof types.MyProfileDocument,
};
const documents: Documents = {
    "\n    query Heartbeat {\n        authStatus {\n            isAuthenticated\n        }\n    }\n": types.HeartbeatDocument,
    "\n    fragment UserProfileFields on UserProfile {\n        firstName\n        lastName\n        displayName\n        phoneNumber\n        avatarUrl\n        createdAt\n        updatedAt\n    }\n": types.UserProfileFieldsFragmentDoc,
    "\n    fragment UserPreferencesFields on UserPreferences {\n        theme\n        language\n        timezone\n        pushWebNotifications\n        notificationsTickets\n        updatedAt\n    }\n": types.UserPreferencesFieldsFragmentDoc,
    "\n    fragment RoleContextFields on RoleContext {\n        roleCode\n        roleName\n        company {\n            id\n            companyCode\n            name\n            logoUrl\n        }\n        dashboardPath\n    }\n": types.RoleContextFieldsFragmentDoc,
    "\n    \n    \n\n    fragment UserFullFields on User {\n        id\n        userCode\n        email\n        emailVerified\n        status\n        authProvider\n        profile {\n            ...UserProfileFields\n        }\n        roleContexts {\n            ...RoleContextFields\n        }\n        ticketsCount\n        resolvedTicketsCount\n        averageRating\n        lastLoginAt\n        createdAt\n        updatedAt\n    }\n": types.UserFullFieldsFragmentDoc,
    "\n    \n\n    fragment UserAuthInfoFields on UserAuthInfo {\n        id\n        userCode\n        email\n        emailVerified\n        onboardingCompleted\n        onboardingCompletedAt\n        status\n        displayName\n        avatarUrl\n        theme\n        language\n        roleContexts {\n            ...RoleContextFields\n        }\n    }\n": types.UserAuthInfoFieldsFragmentDoc,
    "\n    \n\n    fragment AuthPayloadFields on AuthPayload {\n        accessToken\n        refreshToken\n        tokenType\n        expiresIn\n        user {\n            ...UserAuthInfoFields\n        }\n        sessionId\n        loginTimestamp\n    }\n": types.AuthPayloadFieldsFragmentDoc,
    "\n    fragment CompanyMinimalFields on CompanyMinimal {\n        id\n        companyCode\n        name\n        logoUrl\n    }\n": types.CompanyMinimalFieldsFragmentDoc,
    "\n    mutation Register($input: RegisterInput!) {\n        register(input: $input) {\n            accessToken\n            expiresIn\n            user {\n                id\n                email\n                onboardingCompletedAt\n                roleContexts {\n                    roleCode\n                    roleName\n                    dashboardPath\n                }\n            }\n        }\n    }\n": types.RegisterDocument,
    "\n    mutation Login($input: LoginInput!) {\n        login(input: $input) {\n            accessToken\n            expiresIn\n            user {\n                id\n                email\n                onboardingCompletedAt\n                roleContexts {\n                    roleCode\n                    roleName\n                    dashboardPath\n                }\n            }\n        }\n    }\n": types.LoginDocument,
    "\n    \n\n    mutation LoginWithGoogle($input: GoogleLoginInput!) {\n        loginWithGoogle(input: $input) {\n            ...AuthPayloadFields\n        }\n    }\n": types.LoginWithGoogleDocument,
    "\n    mutation Logout($everywhere: Boolean) {\n        logout(everywhere: $everywhere)\n    }\n": types.LogoutDocument,
    "\n    mutation RefreshToken {\n        refreshToken {\n            accessToken\n            refreshToken\n            tokenType\n            expiresIn\n        }\n    }\n": types.RefreshTokenDocument,
    "\n    mutation VerifyEmail($token: String!) {\n        verifyEmail(token: $token) {\n            success\n            message\n            canResend\n            resendAvailableAt\n        }\n    }\n": types.VerifyEmailDocument,
    "\n    mutation ResendVerification {\n        resendVerification {\n            success\n            message\n            canResend\n            resendAvailableAt\n        }\n    }\n": types.ResendVerificationDocument,
    "\n    mutation ResetPassword($email: Email!) {\n        resetPassword(email: $email)\n    }\n": types.ResetPasswordDocument,
    "\n    mutation ConfirmPasswordReset($input: PasswordResetInput!) {\n        confirmPasswordReset(input: $input) {\n            success\n            message\n            user {\n                id\n                email\n                displayName\n            }\n        }\n    }\n": types.ConfirmPasswordResetDocument,
    "\n    mutation MarkOnboardingCompleted {\n        markOnboardingCompleted {\n            success\n            message\n            user {\n                id\n                userCode\n                email\n                emailVerified\n                onboardingCompleted\n                displayName\n                avatarUrl\n                theme\n                language\n                roleContexts {\n                    roleCode\n                    roleName\n                    company {\n                        id\n                        name\n                    }\n                    dashboardPath\n                }\n            }\n        }\n    }\n": types.MarkOnboardingCompletedDocument,
    "\n    mutation UpdateMyProfile($input: UpdateProfileInput!) {\n        updateMyProfile(input: $input) {\n            userId\n            profile {\n                firstName\n                lastName\n                displayName\n                phoneNumber\n                avatarUrl\n                updatedAt\n            }\n            updatedAt\n        }\n    }\n": types.UpdateMyProfileDocument,
    "\n    \n\n    query AuthStatus {\n        authStatus {\n            isAuthenticated\n            user {\n                ...UserAuthInfoFields\n            }\n            currentSession {\n                sessionId\n                deviceName\n                ipAddress\n                lastUsedAt\n                expiresAt\n                isCurrent\n            }\n            tokenInfo {\n                expiresIn\n                issuedAt\n                tokenType\n            }\n        }\n    }\n": types.AuthStatusDocument,
    "\n    query MySessions {\n        mySessions {\n            sessionId\n            deviceName\n            ipAddress\n            userAgent\n            lastUsedAt\n            expiresAt\n            isCurrent\n            location {\n                city\n                country\n            }\n        }\n    }\n": types.MySessionsDocument,
    "\n    query EmailVerificationStatus {\n        emailVerificationStatus {\n            isVerified\n            email\n            verificationSentAt\n            canResend\n            resendAvailableAt\n            attemptsRemaining\n        }\n    }\n": types.EmailVerificationStatusDocument,
    "\n    \n\n    query Me {\n        me {\n            ...UserFullFields\n        }\n    }\n": types.MeDocument,
    "\n    \n\n    query MyProfile {\n        myProfile {\n            ...UserProfileFields\n        }\n    }\n": types.MyProfileDocument,
};

/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 *
 *
 * @example
 * ```ts
 * const query = gql(`query GetUser($id: ID!) { user(id: $id) { name } }`);
 * ```
 *
 * The query argument is unknown!
 * Please regenerate the types.
 */
export function gql(source: string): unknown;

/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function gql(source: "\n    query Heartbeat {\n        authStatus {\n            isAuthenticated\n        }\n    }\n"): (typeof documents)["\n    query Heartbeat {\n        authStatus {\n            isAuthenticated\n        }\n    }\n"];
/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function gql(source: "\n    fragment UserProfileFields on UserProfile {\n        firstName\n        lastName\n        displayName\n        phoneNumber\n        avatarUrl\n        createdAt\n        updatedAt\n    }\n"): (typeof documents)["\n    fragment UserProfileFields on UserProfile {\n        firstName\n        lastName\n        displayName\n        phoneNumber\n        avatarUrl\n        createdAt\n        updatedAt\n    }\n"];
/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function gql(source: "\n    fragment UserPreferencesFields on UserPreferences {\n        theme\n        language\n        timezone\n        pushWebNotifications\n        notificationsTickets\n        updatedAt\n    }\n"): (typeof documents)["\n    fragment UserPreferencesFields on UserPreferences {\n        theme\n        language\n        timezone\n        pushWebNotifications\n        notificationsTickets\n        updatedAt\n    }\n"];
/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function gql(source: "\n    fragment RoleContextFields on RoleContext {\n        roleCode\n        roleName\n        company {\n            id\n            companyCode\n            name\n            logoUrl\n        }\n        dashboardPath\n    }\n"): (typeof documents)["\n    fragment RoleContextFields on RoleContext {\n        roleCode\n        roleName\n        company {\n            id\n            companyCode\n            name\n            logoUrl\n        }\n        dashboardPath\n    }\n"];
/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function gql(source: "\n    \n    \n\n    fragment UserFullFields on User {\n        id\n        userCode\n        email\n        emailVerified\n        status\n        authProvider\n        profile {\n            ...UserProfileFields\n        }\n        roleContexts {\n            ...RoleContextFields\n        }\n        ticketsCount\n        resolvedTicketsCount\n        averageRating\n        lastLoginAt\n        createdAt\n        updatedAt\n    }\n"): (typeof documents)["\n    \n    \n\n    fragment UserFullFields on User {\n        id\n        userCode\n        email\n        emailVerified\n        status\n        authProvider\n        profile {\n            ...UserProfileFields\n        }\n        roleContexts {\n            ...RoleContextFields\n        }\n        ticketsCount\n        resolvedTicketsCount\n        averageRating\n        lastLoginAt\n        createdAt\n        updatedAt\n    }\n"];
/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function gql(source: "\n    \n\n    fragment UserAuthInfoFields on UserAuthInfo {\n        id\n        userCode\n        email\n        emailVerified\n        onboardingCompleted\n        onboardingCompletedAt\n        status\n        displayName\n        avatarUrl\n        theme\n        language\n        roleContexts {\n            ...RoleContextFields\n        }\n    }\n"): (typeof documents)["\n    \n\n    fragment UserAuthInfoFields on UserAuthInfo {\n        id\n        userCode\n        email\n        emailVerified\n        onboardingCompleted\n        onboardingCompletedAt\n        status\n        displayName\n        avatarUrl\n        theme\n        language\n        roleContexts {\n            ...RoleContextFields\n        }\n    }\n"];
/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function gql(source: "\n    \n\n    fragment AuthPayloadFields on AuthPayload {\n        accessToken\n        refreshToken\n        tokenType\n        expiresIn\n        user {\n            ...UserAuthInfoFields\n        }\n        sessionId\n        loginTimestamp\n    }\n"): (typeof documents)["\n    \n\n    fragment AuthPayloadFields on AuthPayload {\n        accessToken\n        refreshToken\n        tokenType\n        expiresIn\n        user {\n            ...UserAuthInfoFields\n        }\n        sessionId\n        loginTimestamp\n    }\n"];
/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function gql(source: "\n    fragment CompanyMinimalFields on CompanyMinimal {\n        id\n        companyCode\n        name\n        logoUrl\n    }\n"): (typeof documents)["\n    fragment CompanyMinimalFields on CompanyMinimal {\n        id\n        companyCode\n        name\n        logoUrl\n    }\n"];
/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function gql(source: "\n    mutation Register($input: RegisterInput!) {\n        register(input: $input) {\n            accessToken\n            expiresIn\n            user {\n                id\n                email\n                onboardingCompletedAt\n                roleContexts {\n                    roleCode\n                    roleName\n                    dashboardPath\n                }\n            }\n        }\n    }\n"): (typeof documents)["\n    mutation Register($input: RegisterInput!) {\n        register(input: $input) {\n            accessToken\n            expiresIn\n            user {\n                id\n                email\n                onboardingCompletedAt\n                roleContexts {\n                    roleCode\n                    roleName\n                    dashboardPath\n                }\n            }\n        }\n    }\n"];
/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function gql(source: "\n    mutation Login($input: LoginInput!) {\n        login(input: $input) {\n            accessToken\n            expiresIn\n            user {\n                id\n                email\n                onboardingCompletedAt\n                roleContexts {\n                    roleCode\n                    roleName\n                    dashboardPath\n                }\n            }\n        }\n    }\n"): (typeof documents)["\n    mutation Login($input: LoginInput!) {\n        login(input: $input) {\n            accessToken\n            expiresIn\n            user {\n                id\n                email\n                onboardingCompletedAt\n                roleContexts {\n                    roleCode\n                    roleName\n                    dashboardPath\n                }\n            }\n        }\n    }\n"];
/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function gql(source: "\n    \n\n    mutation LoginWithGoogle($input: GoogleLoginInput!) {\n        loginWithGoogle(input: $input) {\n            ...AuthPayloadFields\n        }\n    }\n"): (typeof documents)["\n    \n\n    mutation LoginWithGoogle($input: GoogleLoginInput!) {\n        loginWithGoogle(input: $input) {\n            ...AuthPayloadFields\n        }\n    }\n"];
/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function gql(source: "\n    mutation Logout($everywhere: Boolean) {\n        logout(everywhere: $everywhere)\n    }\n"): (typeof documents)["\n    mutation Logout($everywhere: Boolean) {\n        logout(everywhere: $everywhere)\n    }\n"];
/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function gql(source: "\n    mutation RefreshToken {\n        refreshToken {\n            accessToken\n            refreshToken\n            tokenType\n            expiresIn\n        }\n    }\n"): (typeof documents)["\n    mutation RefreshToken {\n        refreshToken {\n            accessToken\n            refreshToken\n            tokenType\n            expiresIn\n        }\n    }\n"];
/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function gql(source: "\n    mutation VerifyEmail($token: String!) {\n        verifyEmail(token: $token) {\n            success\n            message\n            canResend\n            resendAvailableAt\n        }\n    }\n"): (typeof documents)["\n    mutation VerifyEmail($token: String!) {\n        verifyEmail(token: $token) {\n            success\n            message\n            canResend\n            resendAvailableAt\n        }\n    }\n"];
/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function gql(source: "\n    mutation ResendVerification {\n        resendVerification {\n            success\n            message\n            canResend\n            resendAvailableAt\n        }\n    }\n"): (typeof documents)["\n    mutation ResendVerification {\n        resendVerification {\n            success\n            message\n            canResend\n            resendAvailableAt\n        }\n    }\n"];
/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function gql(source: "\n    mutation ResetPassword($email: Email!) {\n        resetPassword(email: $email)\n    }\n"): (typeof documents)["\n    mutation ResetPassword($email: Email!) {\n        resetPassword(email: $email)\n    }\n"];
/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function gql(source: "\n    mutation ConfirmPasswordReset($input: PasswordResetInput!) {\n        confirmPasswordReset(input: $input) {\n            success\n            message\n            user {\n                id\n                email\n                displayName\n            }\n        }\n    }\n"): (typeof documents)["\n    mutation ConfirmPasswordReset($input: PasswordResetInput!) {\n        confirmPasswordReset(input: $input) {\n            success\n            message\n            user {\n                id\n                email\n                displayName\n            }\n        }\n    }\n"];
/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function gql(source: "\n    mutation MarkOnboardingCompleted {\n        markOnboardingCompleted {\n            success\n            message\n            user {\n                id\n                userCode\n                email\n                emailVerified\n                onboardingCompleted\n                displayName\n                avatarUrl\n                theme\n                language\n                roleContexts {\n                    roleCode\n                    roleName\n                    company {\n                        id\n                        name\n                    }\n                    dashboardPath\n                }\n            }\n        }\n    }\n"): (typeof documents)["\n    mutation MarkOnboardingCompleted {\n        markOnboardingCompleted {\n            success\n            message\n            user {\n                id\n                userCode\n                email\n                emailVerified\n                onboardingCompleted\n                displayName\n                avatarUrl\n                theme\n                language\n                roleContexts {\n                    roleCode\n                    roleName\n                    company {\n                        id\n                        name\n                    }\n                    dashboardPath\n                }\n            }\n        }\n    }\n"];
/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function gql(source: "\n    mutation UpdateMyProfile($input: UpdateProfileInput!) {\n        updateMyProfile(input: $input) {\n            userId\n            profile {\n                firstName\n                lastName\n                displayName\n                phoneNumber\n                avatarUrl\n                updatedAt\n            }\n            updatedAt\n        }\n    }\n"): (typeof documents)["\n    mutation UpdateMyProfile($input: UpdateProfileInput!) {\n        updateMyProfile(input: $input) {\n            userId\n            profile {\n                firstName\n                lastName\n                displayName\n                phoneNumber\n                avatarUrl\n                updatedAt\n            }\n            updatedAt\n        }\n    }\n"];
/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function gql(source: "\n    \n\n    query AuthStatus {\n        authStatus {\n            isAuthenticated\n            user {\n                ...UserAuthInfoFields\n            }\n            currentSession {\n                sessionId\n                deviceName\n                ipAddress\n                lastUsedAt\n                expiresAt\n                isCurrent\n            }\n            tokenInfo {\n                expiresIn\n                issuedAt\n                tokenType\n            }\n        }\n    }\n"): (typeof documents)["\n    \n\n    query AuthStatus {\n        authStatus {\n            isAuthenticated\n            user {\n                ...UserAuthInfoFields\n            }\n            currentSession {\n                sessionId\n                deviceName\n                ipAddress\n                lastUsedAt\n                expiresAt\n                isCurrent\n            }\n            tokenInfo {\n                expiresIn\n                issuedAt\n                tokenType\n            }\n        }\n    }\n"];
/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function gql(source: "\n    query MySessions {\n        mySessions {\n            sessionId\n            deviceName\n            ipAddress\n            userAgent\n            lastUsedAt\n            expiresAt\n            isCurrent\n            location {\n                city\n                country\n            }\n        }\n    }\n"): (typeof documents)["\n    query MySessions {\n        mySessions {\n            sessionId\n            deviceName\n            ipAddress\n            userAgent\n            lastUsedAt\n            expiresAt\n            isCurrent\n            location {\n                city\n                country\n            }\n        }\n    }\n"];
/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function gql(source: "\n    query EmailVerificationStatus {\n        emailVerificationStatus {\n            isVerified\n            email\n            verificationSentAt\n            canResend\n            resendAvailableAt\n            attemptsRemaining\n        }\n    }\n"): (typeof documents)["\n    query EmailVerificationStatus {\n        emailVerificationStatus {\n            isVerified\n            email\n            verificationSentAt\n            canResend\n            resendAvailableAt\n            attemptsRemaining\n        }\n    }\n"];
/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function gql(source: "\n    \n\n    query Me {\n        me {\n            ...UserFullFields\n        }\n    }\n"): (typeof documents)["\n    \n\n    query Me {\n        me {\n            ...UserFullFields\n        }\n    }\n"];
/**
 * The gql function is used to parse GraphQL queries into a document that can be used by GraphQL clients.
 */
export function gql(source: "\n    \n\n    query MyProfile {\n        myProfile {\n            ...UserProfileFields\n        }\n    }\n"): (typeof documents)["\n    \n\n    query MyProfile {\n        myProfile {\n            ...UserProfileFields\n        }\n    }\n"];

export function gql(source: string) {
  return (documents as any)[source] ?? {};
}

export type DocumentType<TDocumentNode extends DocumentNode<any, any>> = TDocumentNode extends DocumentNode<  infer TType,  any>  ? TType  : never;