/**
 * RoleBasedSidebar - Sidebar profesional que cambia según el rol del usuario
 * Inspirado en el mockup helpdeskmoqups/components/role-based-sidebar.tsx
 */

import { useState } from 'react';
import { router } from '@inertiajs/react';
import { 
    Ticket, 
    User, 
    Users, 
    Settings, 
    BarChart3, 
    LogOut, 
    Headphones, 
    HelpCircle, 
    MessageSquare, 
    UserPlus, 
    Building, 
    FileText, 
    Bell, 
    Shield,
    ArrowLeftRight,
    Home
} from 'lucide-react';
import { useAuth } from '@/contexts';
import type { RoleContext } from '@/types/models';

interface SidebarItem {
    icon: React.ComponentType<{ className?: string }>;
    href: string;
    label: string;
    badge?: string;
}

export function RoleBasedSidebar() {
    const { user, logout } = useAuth();
    const [showLogoutModal, setShowLogoutModal] = useState(false);
    const currentPath = window.location.pathname;

    // Obtener el rol activo actual
    const getActiveRole = (): string => {
        if (!user?.roleContexts || user.roleContexts.length === 0) return 'USER';

        // Determinar rol activo según la URL actual
        if (currentPath.startsWith('/admin')) return 'PLATFORM_ADMIN';
        if (currentPath.startsWith('/empresa')) return 'COMPANY_ADMIN';
        if (currentPath.startsWith('/agent')) return 'AGENT';
        return 'USER';
    };

    const activeRole = getActiveRole();

    // Items para cada rol
    const regularUserItems: SidebarItem[] = [
        { icon: Home, href: '/tickets', label: 'Inicio' },
        { icon: Ticket, href: '/tickets/create', label: 'Crear Ticket' },
        { icon: Bell, href: '/announcements', label: 'Anuncios' },
        { icon: HelpCircle, href: '/help-center', label: 'Centro de Ayuda' },
        { icon: User, href: '/profile', label: 'Mi Perfil' },
        { icon: Settings, href: '/settings', label: 'Configuración' },
    ];

    const agentItems: SidebarItem[] = [
        { icon: BarChart3, href: '/agent/dashboard', label: 'Dashboard' },
        { icon: Ticket, href: '/agent/tickets', label: 'Todos los Tickets' },
        { icon: MessageSquare, href: '/agent/assigned', label: 'Mis Asignados' },
        { icon: FileText, href: '/agent/knowledge', label: 'Base de Conocimiento' },
        { icon: User, href: '/profile', label: 'Mi Perfil' },
        { icon: Settings, href: '/settings', label: 'Configuración' },
    ];

    const companyAdminItems: SidebarItem[] = [
        { icon: BarChart3, href: '/empresa/dashboard', label: 'Dashboard' },
        { icon: Ticket, href: '/empresa/tickets', label: 'Todos los Tickets' },
        { icon: UserPlus, href: '/empresa/agents', label: 'Gestionar Agentes' },
        { icon: Bell, href: '/empresa/announcements', label: 'Anuncios' },
        { icon: HelpCircle, href: '/empresa/help', label: 'Artículos de Ayuda' },
        { icon: Settings, href: '/empresa/settings', label: 'Config. Empresa' },
    ];

    const platformAdminItems: SidebarItem[] = [
        { icon: Shield, href: '/admin/dashboard', label: 'Dashboard' },
        { icon: Building, href: '/admin/companies', label: 'Empresas' },
        { icon: Users, href: '/admin/users', label: 'Usuarios' },
        { icon: Ticket, href: '/admin/tickets', label: 'Todos los Tickets' },
        { icon: FileText, href: '/admin/requests', label: 'Solicitudes' },
        { icon: Settings, href: '/admin/settings', label: 'Config. Sistema' },
    ];

    // Obtener items según el rol activo
    const getCurrentItems = (): SidebarItem[] => {
        switch (activeRole) {
            case 'AGENT':
                return agentItems;
            case 'COMPANY_ADMIN':
                return companyAdminItems;
            case 'PLATFORM_ADMIN':
                return platformAdminItems;
            default:
                return regularUserItems;
        }
    };

    // Colores según el rol
    const getRoleColor = () => {
        switch (activeRole) {
            case 'AGENT':
                return 'bg-green-600 hover:bg-green-700';
            case 'COMPANY_ADMIN':
                return 'bg-purple-600 hover:bg-purple-700';
            case 'PLATFORM_ADMIN':
                return 'bg-red-600 hover:bg-red-700';
            default:
                return 'bg-blue-600 hover:bg-blue-700';
        }
    };

    const handleLogout = () => {
        if (confirm('¿Estás seguro que deseas cerrar sesión?')) {
            logout();
            router.visit('/');
        }
    };

    const handleChangeRole = () => {
        router.visit('/role-selector');
    };

    const items = getCurrentItems();
    const roleColor = getRoleColor();

    return (
        <aside className="w-16 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col">
            {/* Logo */}
            <div className={`h-16 flex items-center justify-center ${roleColor} transition-colors`}>
                <Headphones className="h-7 w-7 text-white" />
            </div>

            {/* Navigation Items */}
            <nav className="flex-1 py-4 space-y-2 overflow-y-auto">
                {items.map((item, index) => {
                    const Icon = item.icon;
                    const isActive = currentPath === item.href || currentPath.startsWith(item.href + '/');
                    
                    return (
                        <div key={index} className="relative group">
                            <button
                                onClick={() => router.visit(item.href)}
                                className={`
                                    w-full h-12 flex items-center justify-center
                                    transition-colors relative
                                    ${isActive 
                                        ? `text-white ${roleColor}` 
                                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                                    }
                                `}
                                title={item.label}
                            >
                                <Icon className="h-5 w-5" />
                                {item.badge && (
                                    <span className="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full" />
                                )}
                            </button>
                            
                            {/* Tooltip */}
                            <div className="
                                absolute left-full ml-2 top-1/2 -translate-y-1/2
                                px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-sm rounded-lg
                                whitespace-nowrap opacity-0 pointer-events-none group-hover:opacity-100
                                transition-opacity z-50 shadow-lg
                            ">
                                {item.label}
                                <div className="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-gray-900 dark:border-r-gray-700" />
                            </div>
                        </div>
                    );
                })}
            </nav>

            {/* Bottom Actions */}
            <div className="border-t border-gray-200 dark:border-gray-700 py-2 space-y-1">
                {/* Cambiar Rol - Solo si tiene múltiples roles */}
                {user?.roleContexts && user.roleContexts.length > 1 && (
                    <div className="relative group">
                        <button
                            onClick={handleChangeRole}
                            className="w-full h-12 flex items-center justify-center text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            title="Cambiar Rol"
                        >
                            <ArrowLeftRight className="h-5 w-5" />
                        </button>
                        
                        {/* Tooltip */}
                        <div className="
                            absolute left-full ml-2 top-1/2 -translate-y-1/2
                            px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-sm rounded-lg
                            whitespace-nowrap opacity-0 pointer-events-none group-hover:opacity-100
                            transition-opacity z-50 shadow-lg
                        ">
                            Cambiar Rol
                            <div className="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-gray-900 dark:border-r-gray-700" />
                        </div>
                    </div>
                )}

                {/* Logout */}
                <div className="relative group">
                    <button
                        onClick={handleLogout}
                        className="w-full h-12 flex items-center justify-center text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                        title="Cerrar Sesión"
                    >
                        <LogOut className="h-5 w-5" />
                    </button>
                    
                    {/* Tooltip */}
                    <div className="
                        absolute left-full ml-2 top-1/2 -translate-y-1/2
                        px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-sm rounded-lg
                        whitespace-nowrap opacity-0 pointer-events-none group-hover:opacity-100
                        transition-opacity z-50 shadow-lg
                    ">
                        Cerrar Sesión
                        <div className="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-gray-900 dark:border-r-gray-700" />
                    </div>
                </div>
            </div>
        </aside>
    );
}
