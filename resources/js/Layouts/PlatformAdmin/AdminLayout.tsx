/**
 * AdminLayout - Layout para rol PLATFORM_ADMIN (Administrador de Plataforma)
 * Usa AuthenticatedLayout con configuración específica de PLATFORM_ADMIN
 */

import React from 'react';
import { AuthenticatedLayout } from '../Authenticated/AuthenticatedLayout';
import { platformAdminSidebarConfig } from '@/lib/constants/sidebar-configs';

interface AdminLayoutProps {
    title?: string;
    children: React.ReactNode;
}

export const AdminLayout: React.FC<AdminLayoutProps> = ({ title, children }) => {
    return (
        <AuthenticatedLayout
            title={title}
            sidebarConfig={platformAdminSidebarConfig}
            roleIndicator={{
                label: 'Admin Plataforma',
                color: 'bg-red-600',
            }}
        >
            {children}
        </AuthenticatedLayout>
    );
};

