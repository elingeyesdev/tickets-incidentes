import { Head } from '@inertiajs/react';
import { AuthGuard } from '@/components/Auth/AuthGuard';
import { CompanyAdminLayout } from '@/Layouts/CompanyAdmin/CompanyAdminLayout';
import { RoleCode } from '@/types';

export default function CompanyAdminDashboard() {
    return (
        <AuthGuard allowedRoles={[RoleCode.CompanyAdmin]}>
            <CompanyAdminLayout>
                <Head title="Dashboard - Admin de Compañía" />
                <div>
                    <h1 className="text-2xl font-bold">Dashboard de Admin de Compañía</h1>
                    {/* Contenido del dashboard de admin de compañía */}
                </div>
            </CompanyAdminLayout>
        </AuthGuard>
    );
}
