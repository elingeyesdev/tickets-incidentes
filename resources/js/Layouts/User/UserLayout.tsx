/**
 * UserLayout - Layout para rol USER (Cliente Regular)
 * Usa AuthenticatedLayout con configuración específica de USER
 */

import React from 'react';
import { AuthenticatedLayout } from '../Authenticated/AuthenticatedLayout';
import { userSidebarConfig } from '@/lib/constants/sidebar-configs';

interface UserLayoutProps {
    title?: string;
    children: React.ReactNode;
}

export const UserLayout: React.FC<UserLayoutProps> = ({ title, children }) => {
    return (
        <AuthenticatedLayout
            title={title}
            sidebarConfig={userSidebarConfig}
            roleIndicator={{
                label: 'Usuario',
                color: 'bg-green-600',
            }}
        >
            {children}
        </AuthenticatedLayout>
    );
};
