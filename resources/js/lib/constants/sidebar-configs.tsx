/**
 * Sidebar Configurations - Configuración de navegación para cada rol
 * Centralizadas para fácil mantenimiento
 */

import { 
    Ticket, 
    HelpCircle, 
    Bell, 
    User as UserIcon, 
    Settings,
    BarChart3,
    MessageSquare,
    FileText,
    UserPlus,
    Building,
    Shield,
    Users,
} from 'lucide-react';
import type { SidebarSection } from '@/Components/navigation/Sidebar';

// ============================================
// USER (Cliente Regular)
// ============================================
export const userSidebarConfig: SidebarSection[] = [
    {
        title: 'NAVEGACIÓN',
        items: [
            {
                icon: Ticket,
                href: '/tickets',
                label: 'Mis Tickets',
            },
            {
                icon: Bell,
                href: '/announcements',
                label: 'Anuncios',
            },
            {
                icon: HelpCircle,
                href: '/help-center',
                label: 'Centro de Ayuda',
            },
        ],
    },
    {
        title: 'CUENTA',
        items: [
            {
                icon: UserIcon,
                href: '/profile',
                label: 'Perfil',
            },
            {
                icon: Settings,
                href: '/settings',
                label: 'Configuración',
            },
        ],
    },
];

// ============================================
// AGENT (Agente de Soporte)
// ============================================
export const agentSidebarConfig: SidebarSection[] = [
    {
        title: 'PRINCIPAL',
        items: [
            {
                icon: BarChart3,
                href: '/agent/dashboard',
                label: 'Dashboard',
            },
            {
                icon: Ticket,
                href: '/agent/tickets',
                label: 'Todos los Tickets',
            },
            {
                icon: MessageSquare,
                href: '/agent/assigned',
                label: 'Mis Asignados',
            },
            {
                icon: FileText,
                href: '/agent/knowledge',
                label: 'Base de Conocimiento',
            },
        ],
    },
    {
        title: 'CUENTA',
        items: [
            {
                icon: UserIcon,
                href: '/profile',
                label: 'Perfil',
            },
            {
                icon: Settings,
                href: '/settings',
                label: 'Configuración',
            },
        ],
    },
];

// ============================================
// COMPANY_ADMIN (Administrador de Empresa)
// ============================================
export const companyAdminSidebarConfig: SidebarSection[] = [
    {
        title: 'GESTIÓN',
        items: [
            {
                icon: BarChart3,
                href: '/empresa/dashboard',
                label: 'Dashboard',
            },
            {
                icon: Ticket,
                href: '/empresa/tickets',
                label: 'Todos los Tickets',
            },
            {
                icon: UserPlus,
                href: '/empresa/agents',
                label: 'Gestionar Agentes',
            },
            {
                icon: Bell,
                href: '/empresa/announcements',
                label: 'Anuncios',
            },
            {
                icon: HelpCircle,
                href: '/empresa/help-management',
                label: 'Artículos de Ayuda',
            },
        ],
    },
    {
        title: 'CONFIGURACIÓN',
        items: [
            {
                icon: Building,
                href: '/empresa/settings/general',
                label: 'Empresa',
            },
            {
                icon: Settings,
                href: '/empresa/settings/categories',
                label: 'Categorías y Macros',
            },
        ],
    },
];

// ============================================
// PLATFORM_ADMIN (Administrador de Plataforma)
// ============================================
export const platformAdminSidebarConfig: SidebarSection[] = [
    {
        title: 'ADMINISTRACIÓN',
        items: [
            {
                icon: Shield,
                href: '/admin/dashboard',
                label: 'Platform Dashboard',
            },
            {
                icon: Building,
                href: '/admin/companies',
                label: 'Solicitudes de Empresas',
            },
            {
                icon: Users,
                href: '/admin/users',
                label: 'Todos los Usuarios',
            },
        ],
    },
    {
        title: 'SISTEMA',
        items: [
            {
                icon: Settings,
                href: '/admin/system-settings',
                label: 'Configuración del Sistema',
            },
            {
                icon: UserIcon,
                href: '/profile',
                label: 'Mi Perfil',
            },
        ],
    },
];

