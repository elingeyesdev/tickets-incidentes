/**
 * AuthenticatedLayout - Layout base para todos los usuarios autenticados
 * Reutilizable por los 4 roles: USER, AGENT, COMPANY_ADMIN, PLATFORM_ADMIN
 */

import React from 'react';
import { Head } from '@inertiajs/react';
import { 
    useAuth, 
    useTheme, 
    useLocale 
} from '@/contexts';
import { Sidebar, type SidebarSection } from '@/Components/navigation/Sidebar';
import { Button } from '@/Components/ui';
import { Headphones } from 'lucide-react';

interface AuthenticatedLayoutProps {
    title?: string;
    children: React.ReactNode;
    sidebarConfig: SidebarSection[];
    roleIndicator: {
        label: string;
        color: string; // Tailwind class like 'bg-green-600'
    };
}

const AuthenticatedLayoutContent: React.FC<AuthenticatedLayoutProps> = ({ 
    title, 
    children, 
    sidebarConfig,
    roleIndicator 
}) => {
    const { user, logout } = useAuth();
    const { themeMode, resolvedTheme, toggleTheme } = useTheme();
    const { locale, setLocale, t } = useLocale();
    
    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';

    if (!user) {
        if (typeof window !== 'undefined') {
            window.location.href = '/login';
        }
        return null;
    }

    const handleLogout = () => {
        logout();
    };

    return (
        <>
            <Head title={title} />

            <div className="min-h-screen bg-gray-50 dark:bg-gray-900 flex">
                {/* Icon Sidebar - 64px */}
                <div className="w-16 bg-gray-800 dark:bg-gray-900 border-r border-gray-700 flex flex-col flex-shrink-0">
                    {/* Logo */}
                    <div className="flex items-center justify-center py-4 border-b border-gray-700">
                        <div className="flex items-center justify-center w-10 h-10 bg-blue-600 rounded-lg transition-transform hover:scale-110">
                            <Headphones className="w-5 h-5 text-white" />
                        </div>
                    </div>
                    
                    {/* Role Indicator */}
                    <div className="flex-1 flex items-center justify-center py-4">
                        <div 
                            className={`w-10 h-10 ${roleIndicator.color} rounded-lg flex items-center justify-center text-white font-bold text-xs shadow-lg`}
                            title={roleIndicator.label}
                        >
                            {roleIndicator.label.charAt(0)}
                        </div>
                    </div>
                </div>

                {/* Main Sidebar - 256px */}
                <aside className="w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col flex-shrink-0">
                    <Sidebar sections={sidebarConfig} currentPath={currentPath} />
                </aside>

                {/* Main Content Area */}
                <div className="flex-1 flex flex-col min-w-0">
                    {/* Top Navbar */}
                    <header className="h-16 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between px-6 flex-shrink-0">
                        <div className="flex items-center gap-4 min-w-0">
                            <h2 className="text-xl font-semibold text-gray-900 dark:text-white truncate">
                                {title || 'Dashboard'}
                            </h2>
                        </div>

                        <div className="flex items-center gap-3 flex-shrink-0">
                            {/* Language Switcher */}
                            <button
                                onClick={() => setLocale(locale === 'es' ? 'en' : 'es')}
                                className="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors"
                                title={t('language.change')}
                            >
                                <span className="text-base">{locale === 'es' ? '🇪🇸' : '🇺🇸'}</span>
                            </button>

                            {/* Theme Switcher */}
                            <button
                                onClick={toggleTheme}
                                className="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors"
                                title={`${t('common.theme')}: ${t(`theme.${themeMode}`)}`}
                            >
                                {themeMode === 'system' ? '🖥️' : resolvedTheme === 'dark' ? '🌙' : '☀️'}
                            </button>

                            {/* User Info */}
                            <div className="flex items-center gap-3 pl-3 border-l border-gray-200 dark:border-gray-700">
                                <div className="text-right hidden md:block">
                                    <div className="text-sm font-medium text-gray-900 dark:text-white">
                                        {user.profile.displayName}
                                    </div>
                                    <div className="text-xs text-gray-500 dark:text-gray-400">
                                        {roleIndicator.label}
                                    </div>
                                </div>
                                <img
                                    src={user.profile.avatarUrl || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.profile.displayName)}&background=random`}
                                    alt={user.profile.displayName}
                                    className={`h-9 w-9 rounded-full ring-2 ${roleIndicator.color.replace('bg-', 'ring-')}`}
                                />
                            </div>

                            {/* Change Role (solo si tiene 2+ roles) */}
                            {user.roleContexts && user.roleContexts.length > 1 && (
                                <Button 
                                    variant="outline" 
                                    size="sm" 
                                    onClick={() => window.location.href = '/role-selector'}
                                    className="hidden sm:inline-flex"
                                >
                                    Cambiar Rol
                                </Button>
                            )}

                            {/* Logout */}
                            <Button variant="ghost" size="sm" onClick={handleLogout}>
                                {t('common.logout')}
                            </Button>
                        </div>
                    </header>

                    {/* Page Content */}
                    <main className="flex-1 overflow-y-auto p-6">
                        <div className="max-w-7xl mx-auto">
                            {children}
                        </div>
                    </main>
                </div>
            </div>
        </>
    );
};

// Layout exportado SIN providers duplicados (ya están en app.tsx)
export const AuthenticatedLayout: React.FC<AuthenticatedLayoutProps> = (props) => {
    return <AuthenticatedLayoutContent {...props} />;
};
