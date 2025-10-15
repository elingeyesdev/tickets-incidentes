/**
 * Onboarding Layout
 * Layout ultra-limpio y centrado para el proceso de onboarding
 * Diseño enfocado en UX sin distracciones, flujo guiado paso a paso
 */

import React from 'react';
import { Head } from '@inertiajs/react';
import { useAuth } from '@/contexts';
import { LogOut } from 'lucide-react';

interface OnboardingLayoutProps {
    title?: string;
    children: React.ReactNode;
}

const OnboardingLayoutContent: React.FC<OnboardingLayoutProps> = ({ 
    title, 
    children
}) => {
    const { user, logout, loading: authLoading } = useAuth();

    // Redirigir si no hay usuario autenticado
    if (!authLoading && !user) {
        window.location.href = '/login';
        return null;
    }

    const handleLogout = () => {
        if (confirm('¿Estás seguro que deseas salir del proceso de configuración?')) {
            logout();
        }
    };

    return (
        <>
            <Head title={title} />

            <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center p-4">
                {/* Solo botón de cerrar sesión - esquina superior derecha */}
                <button
                    onClick={handleLogout}
                    className="fixed top-6 right-6 z-50 p-3 rounded-lg bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 shadow-lg hover:shadow-xl transition-all duration-300 animate-[fadeIn_0.6s_ease-out] group"
                    title="Salir del proceso de configuración"
                >
                    <LogOut className="w-5 h-5 group-hover:scale-110 transition-transform" />
                </button>

                {/* Main Content - Centrado con transición suave */}
                <main className="w-full max-w-4xl mx-auto">
                    {/* Contenido con animación horizontal tipo slider */}
                    <div className="animate-[slideInRight_0.5s_cubic-bezier(0.34,1.56,0.64,1)]">
                        {children}
                    </div>
                </main>
            </div>
        </>
    );
};

// Layout exportado SIN providers duplicados (ya están en app.tsx)
export const OnboardingLayout: React.FC<OnboardingLayoutProps> = ({ 
    title, 
    children
}) => {
    return (
        <OnboardingLayoutContent 
            title={title}
        >
            {children}
        </OnboardingLayoutContent>
    );
};

