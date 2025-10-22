import { Head } from '@inertiajs/react';
import { AuthGuard } from '@/components/Auth/AuthGuard';
import { AdminLayout } from '@/Layouts/PlatformAdmin/AdminLayout';
import { RoleCode } from '@/types';

export default function PlatformAdminDashboard() {
    return (
        <AuthGuard allowedRoles={[RoleCode.PlatformAdmin]}>
            <AdminLayout>
                <Head title="Dashboard - Admin de Plataforma" />
                <div>
                    <h1 className="text-2xl font-bold">Dashboard de Admin de Plataforma</h1>
                    {/* Contenido del dashboard de admin de plataforma */}
                </div>
            </AdminLayout>
        </AuthGuard>
    );
}
