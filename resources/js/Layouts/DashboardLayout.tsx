/**
 * DashboardLayout - Layout profesional para todas las zonas autenticadas
 * Usa el RoleBasedSidebar que cambia según el rol del usuario
 */

import { ReactNode } from 'react';
import { Head } from '@inertiajs/react';
import { RoleBasedSidebar } from '@/Components/layout/RoleBasedSidebar';

interface DashboardLayoutProps {
    children: ReactNode;
    title?: string;
}

export function DashboardLayout({ children, title }: DashboardLayoutProps) {
    return (
        <>
            {title && <Head title={title} />}
            
            <div className="flex min-h-screen bg-gray-50 dark:bg-gray-900">
                {/* Sidebar de Iconos (64px fijo) */}
                <RoleBasedSidebar />
                
                {/* Contenido Principal */}
                <div className="flex-1 flex flex-col">
                    {/* Main Content Area */}
                    <main className="flex-1 overflow-auto">
                        {children}
                    </main>
                </div>
            </div>
        </>
    );
}

