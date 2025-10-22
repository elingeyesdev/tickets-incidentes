import { Head } from '@inertiajs/react';
import { AuthGuard } from '@/components/Auth/AuthGuard';
import { AgentLayout } from '@/Layouts/Agent/AgentLayout';
import { RoleCode } from '@/types';

export default function AgentDashboard() {
    return (
        <AuthGuard allowedRoles={[RoleCode.Agent]}>
            <AgentLayout>
                <Head title="Dashboard - Agente" />
                <div>
                    <h1 className="text-2xl font-bold">Dashboard de Agente</h1>
                    {/* Contenido del dashboard de agente */}
                </div>
            </AgentLayout>
        </AuthGuard>
    );
}
