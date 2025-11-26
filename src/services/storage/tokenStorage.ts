import * as SecureStore from 'expo-secure-store';

const ACCESS_TOKEN_KEY = 'auth_access_token';

export const tokenStorage = {
    async setAccessToken(token: string) {
        await SecureStore.setItemAsync(ACCESS_TOKEN_KEY, token);
    },

    async getAccessToken() {
        return await SecureStore.getItemAsync(ACCESS_TOKEN_KEY);
    },

    async clearAccessToken() {
        await SecureStore.deleteItemAsync(ACCESS_TOKEN_KEY);
    },
};
