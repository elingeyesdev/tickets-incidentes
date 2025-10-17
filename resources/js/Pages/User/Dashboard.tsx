/**
 * User Dashboard - Dashboard para usuarios finales
 * Permite crear y gestionar tickets de soporte
 */

import { Head } from '@inertiajs/react';
import { ProtectedRoute } from '@/Components/guards';
import { UserLayout } from '@/Layouts/User/UserLayout';
import { Card } from '@/Components/ui';
import { Ticket, Plus, Clock, CheckCircle } from 'lucide-react';

export default function UserDashboard() {
    return (
        <ProtectedRoute allowedRoles={['USER']}>
            <UserLayout title="Mis Tickets">
                <Head title="Dashboard - Usuario" />

                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        Bienvenido a tu Dashboard
                    </h1>
                    <p className="text-gray-600 dark:text-gray-400">
                        Gestiona tus tickets de soporte de manera fácil y rápida
                    </p>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <Card>
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
                                <Ticket className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-600 dark:text-gray-400">Tickets Activos</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">0</p>
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-yellow-100 dark:bg-yellow-900/20 rounded-lg">
                                <Clock className="h-6 w-6 text-yellow-600 dark:text-yellow-400" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-600 dark:text-gray-400">Pendientes</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">0</p>
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-green-100 dark:bg-green-900/20 rounded-lg">
                                <CheckCircle className="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-600 dark:text-gray-400">Resueltos</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">0</p>
                            </div>
                        </div>
                    </Card>
                </div>

                {/* Quick Actions */}
                <Card>
                    <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        Acciones Rápidas
                    </h2>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <button className="flex items-center gap-3 p-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg hover:border-blue-500 dark:hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/10 transition-all group">
                            <div className="p-2 bg-blue-100 dark:bg-blue-900/20 rounded-lg group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                <Plus className="h-5 w-5 text-blue-600 dark:text-blue-400 group-hover:text-white" />
                            </div>
                            <div className="text-left">
                                <p className="font-medium text-gray-900 dark:text-white">Crear Nuevo Ticket</p>
                                <p className="text-sm text-gray-600 dark:text-gray-400">Reporta un problema o solicita ayuda</p>
                            </div>
                        </button>

                        <button className="flex items-center gap-3 p-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg hover:border-purple-500 dark:hover:border-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/10 transition-all group">
                            <div className="p-2 bg-purple-100 dark:bg-purple-900/20 rounded-lg group-hover:bg-purple-600 group-hover:text-white transition-colors">
                                <Ticket className="h-5 w-5 text-purple-600 dark:text-purple-400 group-hover:text-white" />
                            </div>
                            <div className="text-left">
                                <p className="font-medium text-gray-900 dark:text-white">Ver Mis Tickets</p>
                                <p className="text-sm text-gray-600 dark:text-gray-400">Revisa el estado de tus solicitudes</p>
                            </div>
                        </button>
                    </div>
                </Card>
            </UserLayout>
        </ProtectedRoute>
    );
}
