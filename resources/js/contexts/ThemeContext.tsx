/**
 * ThemeContext - Gestión Global de Tema
 * 
 * Responsabilidades:
 * - Tema actual (light/dark/system)
 * - Sincronización con localStorage
 * - Sincronización con preferencias del usuario en BD
 * - Aplicar tema al DOM respetando preferencia del sistema
 */

import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { useAuth } from './AuthContext';

type ThemeMode = 'light' | 'dark' | 'system';
type ResolvedTheme = 'light' | 'dark';

interface ThemeContextType {
    themeMode: ThemeMode;
    resolvedTheme: ResolvedTheme;
    setThemeMode: (mode: ThemeMode) => void;
    toggleTheme: () => void;
    syncWithUserPreferences: () => void;
}

const ThemeContext = createContext<ThemeContextType | undefined>(undefined);

const THEME_STORAGE_KEY = 'helpdesk_theme';

interface ThemeProviderProps {
    children: ReactNode;
}

export const ThemeProvider: React.FC<ThemeProviderProps> = ({ children }) => {
    const { user } = useAuth();

    /**
     * Obtener preferencia del sistema
     */
    const getSystemTheme = (): ResolvedTheme => {
        if (typeof window !== 'undefined' && window.matchMedia) {
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        return 'light';
    };

    /**
     * Inicializar modo de tema con esta prioridad:
     * 1. Preferencia del usuario en BD (si está autenticado)
     * 2. localStorage
     * 3. Default: 'system'
     */
    const getInitialThemeMode = (): ThemeMode => {
        const stored = localStorage.getItem(THEME_STORAGE_KEY);
        if (stored === 'light' || stored === 'dark' || stored === 'system') {
            return stored;
        }
        return 'system';
    };

    const [themeMode, setThemeModeState] = useState<ThemeMode>(getInitialThemeMode);
    const [systemTheme, setSystemTheme] = useState<ResolvedTheme>(getSystemTheme);

    /**
     * Resolver tema actual (light o dark)
     */
    const resolvedTheme: ResolvedTheme = themeMode === 'system' ? systemTheme : themeMode;

    /**
     * Escuchar cambios en la preferencia del sistema
     */
    useEffect(() => {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        const handleChange = (e: MediaQueryListEvent) => {
            setSystemTheme(e.matches ? 'dark' : 'light');
        };

        // Escuchar cambios
        mediaQuery.addEventListener('change', handleChange);
        
        return () => mediaQuery.removeEventListener('change', handleChange);
    }, []);

    /**
     * Aplicar tema al DOM
     */
    useEffect(() => {
        const root = document.documentElement;

        if (resolvedTheme === 'dark') {
            root.classList.add('dark');
        } else {
            root.classList.remove('dark');
        }

        // Guardar en localStorage
        localStorage.setItem(THEME_STORAGE_KEY, themeMode);
    }, [resolvedTheme, themeMode]);

    /**
     * Cambiar modo de tema
     */
    const setThemeMode = (newMode: ThemeMode) => {
        setThemeModeState(newMode);
    };

    /**
     * Toggle entre light, dark, system
     */
    const toggleTheme = () => {
        setThemeModeState((prev) => {
            if (prev === 'light') return 'dark';
            if (prev === 'dark') return 'system';
            return 'light';
        });
    };

    /**
     * Forzar sincronización con preferencias del usuario
     */
    const syncWithUserPreferences = () => {
        // Note: UserAuthInfo has theme at top level, not nested in preferences
        if (user?.theme) {
            const userTheme = user.theme as ThemeMode;
            setThemeModeState(userTheme);
        }
    };

    const value: ThemeContextType = {
        themeMode,
        resolvedTheme,
        setThemeMode,
        toggleTheme,
        syncWithUserPreferences,
    };

    return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
};

/**
 * Hook para usar el contexto de tema
 */
export const useTheme = (): ThemeContextType => {
    const context = useContext(ThemeContext);
    if (context === undefined) {
        throw new Error('useTheme must be used within a ThemeProvider');
    }
    return context;
};

