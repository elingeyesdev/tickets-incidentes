/**
 * Contexts - Barrel Export
 * Centraliza todos los contexts y sus hooks para imports limpios
 */

// Providers
export { AuthProvider } from './AuthContext';
export { ThemeProvider } from './ThemeContext';
export { LocaleProvider } from './LocaleContext';
export { NotificationProvider } from './NotificationContext';

// Hooks
export { useAuth } from './AuthContext';
export { useTheme } from './ThemeContext';
export { useLocale } from './LocaleContext';
export { useNotification } from './NotificationContext';

