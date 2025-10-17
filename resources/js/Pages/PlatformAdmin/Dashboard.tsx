/**
 * Platform Admin Dashboard - Dashboard para administradores de plataforma
 * Control total sobre la plataforma, empresas y usuarios
 */

import { Head } from '@inertiajs/react';
import { ProtectedRoute } from '@/Components/guards';
import { AdminLayout } from '@/Layouts/PlatformAdmin/AdminLayout';
import { Card } from '@/Components/ui';
import { Building2, Users, ShieldCheck, Activity } from 'lucide-react';

export default function PlatformAdminDashboard() {
    return (
        <ProtectedRoute allowedRoles={['PLATFORM_ADMIN']}>
            <AdminLayout title="Dashboard de Plataforma">
                <Head title="Dashboard - Admin de Plataforma" />

                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        Panel de Administración de Plataforma
                    </h1>
                    <p className="text-gray-600 dark:text-gray-400">
                        Control total sobre empresas, usuarios y sistema
                    </p>
                </div>

                {/* Stats Grid */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <Card>
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-red-100 dark:bg-red-900/20 rounded-lg">
                                <Building2 className="h-6 w-6 text-red-600 dark:text-red-400" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-600 dark:text-gray-400">Empresas</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">0</p>
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
                                <Users className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-600 dark:text-gray-400">Usuarios</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">0</p>
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-purple-100 dark:bg-purple-900/20 rounded-lg">
                                <ShieldCheck className="h-6 w-6 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-600 dark:text-gray-400">Solicitudes</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">0</p>
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-green-100 dark:bg-green-900/20 rounded-lg">
                                <Activity className="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-600 dark:text-gray-400">Estado</p>
                                <p className="text-2xl font-bold text-green-600 dark:text-green-400">Activo</p>
                            </div>
                        </div>
                    </Card>
                </div>

                {/* Placeholder para contenido futuro */}
                <Card>
                    <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        Actividad del Sistema
                    </h2>
                    <p className="text-gray-600 dark:text-gray-400">
                        Dashboard de administración de plataforma.
                    </p>
                </Card>
            </AdminLayout>
        </ProtectedRoute>
    );
}
