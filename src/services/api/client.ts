import axios, { AxiosError, InternalAxiosRequestConfig } from 'axios';
import { tokenStorage } from '../storage/tokenStorage';
import { router } from 'expo-router';

const BASE_URL = process.env.EXPO_PUBLIC_API_URL;

export const client = axios.create({
    baseURL: BASE_URL,
    headers: {
        'Content-Type': 'application/json',
    },
    withCredentials: true, // For HttpOnly cookies (refresh token)
});

// Request Interceptor
client.interceptors.request.use(
    async (config: InternalAxiosRequestConfig) => {
        const token = await tokenStorage.getAccessToken();
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => Promise.reject(error)
);

// Response Interceptor
interface RetryQueueItem {
    resolve: (value?: any) => void;
    reject: (error?: any) => void;
    config: InternalAxiosRequestConfig;
}

let isRefreshing = false;
let failedQueue: RetryQueueItem[] = [];

const processQueue = (error: any, token: string | null = null) => {
    failedQueue.forEach((prom) => {
        if (error) {
            prom.reject(error);
        } else {
            if (token) {
                prom.config.headers.Authorization = `Bearer ${token}`;
            }
            client(prom.config)
                .then(prom.resolve)
                .catch(prom.reject);
        }
    });
    failedQueue = [];
};

client.interceptors.response.use(
    (response) => response,
    async (error: AxiosError) => {
        const originalRequest = error.config as InternalAxiosRequestConfig & { _retry?: boolean };

        if (error.response?.status === 401 && !originalRequest._retry) {
            if (isRefreshing) {
                return new Promise(function (resolve, reject) {
                    failedQueue.push({ resolve, reject, config: originalRequest });
                });
            }

            originalRequest._retry = true;
            isRefreshing = true;

            try {
                // Call refresh endpoint
                // We use a separate axios call or the same client but skip interceptor if needed?
                // Usually refresh endpoint doesn't need access token, it uses cookie.
                // But if we use 'client', it will attach the old (invalid) token.
                // It's better to use a clean instance or just axios.post
                const response = await axios.post(`${BASE_URL}/api/auth/refresh`, {}, {
                    withCredentials: true,
                });

                const { data } = response.data; // Assuming structure { success: true, data: { accessToken: '...' } }
                // Adjust based on actual API response structure for refresh
                // Prompt says: "Refrescar access token" -> returns new access token?
                // Let's assume standard response format: { success: true, data: { accessToken: string } }

                // Wait, prompt says "Formato de Respuestas: JSON con estructura { success: boolean, data: T, message?: string }"

                const newAccessToken = data?.accessToken;

                if (newAccessToken) {
                    await tokenStorage.setAccessToken(newAccessToken);
                    client.defaults.headers.common['Authorization'] = `Bearer ${newAccessToken}`;
                    processQueue(null, newAccessToken);
                    return client(originalRequest);
                } else {
                    throw new Error("No access token in refresh response");
                }

            } catch (refreshError) {
                processQueue(refreshError, null);
                await tokenStorage.clearAccessToken();
                router.replace('/(auth)/login');
                return Promise.reject(refreshError);
            } finally {
                isRefreshing = false;
            }
        }

        return Promise.reject(error);
    }
);
