/**
 * Company Admin Dashboard - Dashboard para administradores de empresa
 * Permite gestionar empresa, agentes, configuraciones y contenido
 */

import { Head } from '@inertiajs/react';
import { ProtectedRoute } from '@/Components/guards';
import { CompanyAdminLayout } from '@/Layouts/CompanyAdmin/CompanyAdminLayout';
import { Card } from '@/Components/ui';
import { Users, Ticket, TrendingUp, Settings } from 'lucide-react';

export default function CompanyAdminDashboard() {
    return (
        <ProtectedRoute allowedRoles={['COMPANY_ADMIN' as const]}>
            <CompanyAdminLayout title="Dashboard de Administración">
                <Head title="Dashboard - Admin de Empresa" />

                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        Panel de Administración
                    </h1>
                    <p className="text-gray-600 dark:text-gray-400">
                        Gestiona tu empresa, equipo y configuraciones
                    </p>
                </div>

                {/* Stats Grid */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <Card>
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-purple-100 dark:bg-purple-900/20 rounded-lg">
                                <Users className="h-6 w-6 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-600 dark:text-gray-400">Agentes</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">0</p>
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
                                <Ticket className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-600 dark:text-gray-400">Tickets Totales</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">0</p>
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-green-100 dark:bg-green-900/20 rounded-lg">
                                <TrendingUp className="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-600 dark:text-gray-400">Tasa Resolución</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">-%</p>
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-orange-100 dark:bg-orange-900/20 rounded-lg">
                                <Settings className="h-6 w-6 text-orange-600 dark:text-orange-400" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-600 dark:text-gray-400">Categorías</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">0</p>
                            </div>
                        </div>
                    </Card>
                </div>

                {/* Placeholder para contenido futuro */}
                <Card>
                    <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        Resumen de Actividad
                    </h2>
                    <p className="text-gray-600 dark:text-gray-400">
                        Configura tu empresa para comenzar.
                    </p>
                </Card>
            </CompanyAdminLayout>
        </ProtectedRoute>
    );
}
