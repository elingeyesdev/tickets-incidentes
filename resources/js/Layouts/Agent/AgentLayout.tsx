/**
 * AgentLayout - Layout para rol AGENT (Agente de Soporte)
 * Usa AuthenticatedLayout con configuración específica de AGENT
 */

import React from 'react';
import { AuthenticatedLayout } from '../Authenticated/AuthenticatedLayout';
import { agentSidebarConfig } from '@/lib/constants/sidebar-configs';

interface AgentLayoutProps {
    title?: string;
    children: React.ReactNode;
}

export const AgentLayout: React.FC<AgentLayoutProps> = ({ title, children }) => {
    return (
        <AuthenticatedLayout
            title={title}
            sidebarConfig={agentSidebarConfig}
            roleIndicator={{
                label: 'Agente',
                color: 'bg-blue-600',
            }}
        >
            {children}
        </AuthenticatedLayout>
    );
};
