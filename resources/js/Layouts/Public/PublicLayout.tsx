/**
 * PublicLayout - Layout para Área Pública
 * 
 * Usado en:
 * - Página de bienvenida
 * - Login/Register
 * - Solicitud de empresa
 * - Páginas informativas
 */

import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Headphones } from 'lucide-react';
import { 
    useAuth, 
    useTheme, 
    useLocale 
} from '@/contexts';
import { Button } from '@/Components/ui';

interface PublicLayoutProps {
    title?: string;
    children: React.ReactNode;
    showNavbar?: boolean;
    showFooter?: boolean;
}

const PublicLayoutContent: React.FC<PublicLayoutProps> = ({ 
    title, 
    children,
    showNavbar = true,
    showFooter = true
}) => {
    const { isAuthenticated, user, loading } = useAuth();
    const { themeMode, resolvedTheme, toggleTheme } = useTheme();
    const { locale, setLocale, t } = useLocale();
    
    // Detectar ruta actual para breadcrumb
    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';
    
    const getBreadcrumb = () => {
        if (currentPath === '/solicitud-empresa') return t('breadcrumb.register_company');
        if (currentPath === '/login') return t('breadcrumb.login');
        if (currentPath === '/register-user') return t('breadcrumb.register');
        if (currentPath.startsWith('/verify-email')) return t('breadcrumb.verify_email');
        return null;
    };
    
    const breadcrumb = getBreadcrumb();

    return (
        <>
            <Head title={title} />

            <div className="min-h-screen flex flex-col bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800">
                {/* Navbar */}
                {showNavbar && (
                    <header className="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm shadow-sm">
                        <div className="container mx-auto px-4 py-4">
                            <div className="flex items-center justify-between">
                                {/* Logo + Breadcrumb */}
                                <div className="flex items-center gap-3">
                                    <Link href="/" className="flex items-center gap-3 hover:opacity-80 transition-opacity">
                                        <div className="flex items-center justify-center w-10 h-10 bg-blue-600 rounded-lg">
                                            <Headphones className="w-6 h-6 text-white" />
                                        </div>
                                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">HELPDESK</h1>
                                    </Link>
                                    
                                    {/* Breadcrumb dinámico */}
                                    {breadcrumb && (
                                        <div className="flex items-center gap-2 ml-2 animate-slideInLeft">
                                            <span className="text-gray-400 dark:text-gray-500">/</span>
                                            <span className="text-lg font-semibold text-blue-600 dark:text-blue-400">
                                                {breadcrumb}
                                            </span>
                                        </div>
                                    )}
                                </div>

                                {/* Navigation */}
                                <nav className="flex items-center gap-2">
                                    {/* Language Switcher with Label */}
                                    <button
                                        onClick={() => setLocale(locale === 'es' ? 'en' : 'es')}
                                        className="flex items-center gap-1.5 px-2.5 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-colors"
                                        title={t('language.change')}
                                    >
                                        <span className="text-base">{locale === 'es' ? '🇪🇸' : '🇺🇸'}</span>
                                        <span className="text-sm font-medium">{locale === 'es' ? 'ES' : 'EN'}</span>
                                    </button>

                                    {/* Theme Switcher */}
                                    <button
                                        onClick={toggleTheme}
                                        className="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors"
                                        title={`${t('common.theme')}: ${t(`theme.${themeMode}`)}`}
                                    >
                                        {themeMode === 'system' ? '🖥️' : resolvedTheme === 'dark' ? '🌙' : '☀️'}
                                    </button>

                                    {/* Divider */}
                                    <div className="h-6 w-px bg-gray-300 dark:bg-gray-600 mx-1"></div>

                                    {/* Auth Buttons */}
                                    {!loading && isAuthenticated && user ? (
                                        <Link href="/dashboard">
                                            <Button>
                                                {t('nav.dashboard')}
                                            </Button>
                                        </Link>
                                    ) : (
                                        <>
                                            <Link href="/login">
                                                <Button 
                                                    variant="ghost" 
                                                    size="sm"
                                                    disabled={loading}
                                                >
                                                    {t('common.login')}
                                                </Button>
                                            </Link>
                                            <Link href="/solicitud-empresa">
                                                <Button 
                                                    variant="outline" 
                                                    size="sm"
                                                    disabled={loading}
                                                >
                                                    {t('welcome.hero.btn_company')}
                                                </Button>
                                            </Link>
                                            <Link href="/register-user">
                                                <Button 
                                                    size="sm" 
                                                    className="bg-gray-900 hover:bg-gray-800 dark:bg-gray-100 dark:hover:bg-gray-200 dark:text-gray-900"
                                                    disabled={loading}
                                                >
                                                    {t('welcome.hero.btn_user')}
                                                </Button>
                                            </Link>
                                        </>
                                    )}
                                </nav>
                            </div>
                        </div>
                    </header>
                )}

                {/* Main Content */}
                <main className="flex-grow">
                    {children}
                </main>

                {/* Footer */}
                {showFooter && (
                    <footer className="bg-gray-900 dark:bg-gray-950 text-white py-6">
                        <div className="container mx-auto px-4">
                            <div className="flex items-center justify-center gap-2 mb-3">
                                <div className="flex items-center justify-center w-8 h-8 bg-blue-600 rounded-lg">
                                    <Headphones className="w-5 h-5 text-white" />
                                </div>
                                <h1 className="text-lg font-bold">HELPDESK</h1>
                            </div>
                            <div className="text-center text-sm text-gray-400">
                                <p>&copy; {new Date().getFullYear()} {t('welcome.footer.copyright')}</p>
                            </div>
                        </div>
                    </footer>
                )}
            </div>
        </>
    );
};

// Layout exportado SIN providers duplicados (ya están en app.tsx)
export const PublicLayout: React.FC<PublicLayoutProps> = ({ title, children, showNavbar, showFooter }) => {
    return (
        <PublicLayoutContent title={title} showNavbar={showNavbar} showFooter={showFooter}>
            {children}
        </PublicLayoutContent>
    );
};

