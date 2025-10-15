/**
 * Internationalization Configuration
 * Configuración de idiomas soportados
 */

export type Locale = 'es' | 'en';

export interface I18nConfig {
    defaultLocale: Locale;
    supportedLocales: Locale[];
    fallbackLocale: Locale;
}

export const i18nConfig: I18nConfig = {
    defaultLocale: 'es',
    supportedLocales: ['es', 'en'],
    fallbackLocale: 'es',
};

/**
 * Metadatos de idiomas
 */
export const localeMetadata = {
    es: {
        name: 'Español',
        flag: '🇪🇸',
        code: 'es',
        direction: 'ltr',
    },
    en: {
        name: 'English',
        flag: '🇺🇸',
        code: 'en',
        direction: 'ltr',
    },
} as const;

