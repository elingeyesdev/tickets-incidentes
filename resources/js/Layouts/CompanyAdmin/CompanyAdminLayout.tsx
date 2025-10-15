/**
 * CompanyAdminLayout - Layout para rol COMPANY_ADMIN (Administrador de Empresa)
 * Usa AuthenticatedLayout con configuración específica de COMPANY_ADMIN
 */

import React from 'react';
import { AuthenticatedLayout } from '../Authenticated/AuthenticatedLayout';
import { companyAdminSidebarConfig } from '@/lib/constants/sidebar-configs';

interface CompanyAdminLayoutProps {
    title?: string;
    children: React.ReactNode;
}

export const CompanyAdminLayout: React.FC<CompanyAdminLayoutProps> = ({ title, children }) => {
    return (
        <AuthenticatedLayout
            title={title}
            sidebarConfig={companyAdminSidebarConfig}
            roleIndicator={{
                label: 'Admin Empresa',
                color: 'bg-purple-600',
            }}
        >
            {children}
        </AuthenticatedLayout>
    );
};

