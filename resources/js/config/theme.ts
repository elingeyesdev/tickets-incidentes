/**
 * Theme Configuration
 * Configuración de temas de la aplicación
 */

export type ThemeMode = 'light' | 'dark' | 'system';

export interface ThemeConfig {
    mode: ThemeMode;
    primaryColor: string;
    secondaryColor: string;
}

export const defaultTheme: ThemeConfig = {
    mode: 'system',
    primaryColor: 'blue',
    secondaryColor: 'purple',
};

/**
 * Colores por rol
 */
export const roleColors = {
    USER: {
        primary: 'green',
        ring: 'ring-green-500',
        bg: 'bg-green-600',
        text: 'text-green-600',
    },
    AGENT: {
        primary: 'blue',
        ring: 'ring-blue-500',
        bg: 'bg-blue-600',
        text: 'text-blue-600',
    },
    COMPANY_ADMIN: {
        primary: 'purple',
        ring: 'ring-purple-500',
        bg: 'bg-purple-600',
        text: 'text-purple-600',
    },
    PLATFORM_ADMIN: {
        primary: 'red',
        ring: 'ring-red-500',
        bg: 'bg-red-600',
        text: 'text-red-600',
    },
} as const;

