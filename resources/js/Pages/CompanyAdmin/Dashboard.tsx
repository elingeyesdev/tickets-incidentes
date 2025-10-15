/**
 * Company Admin Dashboard - Dashboard para rol COMPANY_ADMIN
 */

import { DashboardLayout } from '@/Layouts/DashboardLayout';
import { useAuth } from '@/contexts';
import { Shield, Ticket, UserPlus, Bell, Building, Sparkles } from 'lucide-react';

export default function Dashboard() {
    const { user } = useAuth();

    return (
        <DashboardLayout title="Dashboard - Administrador de Empresa">
            <div className="p-8">
                {/* Welcome Header */}
                <div className="mb-8">
                    <div className="flex items-center gap-2 mb-2">
                        <Sparkles className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                        <span className="text-sm font-medium text-purple-600 dark:text-purple-400">
                            Dashboard de Administrador de Empresa
                        </span>
                    </div>
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                        ¡Bienvenido, {user?.displayName || user?.profile?.firstName || 'Administrador'}!
                    </h1>
                    <p className="text-gray-600 dark:text-gray-400 mt-1">
                        Administra tu empresa, equipo y configuraciones
                    </p>
                </div>

                {/* Coming Soon Section */}
                <div className="max-w-4xl">
                    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12">
                        <div className="text-center">
                            <div className="w-20 h-20 mx-auto mb-6 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                                <Building className="w-10 h-10 text-purple-600 dark:text-purple-400" />
                            </div>
                            
                            <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                                Próximamente
                            </h2>
                            
                            <p className="text-gray-600 dark:text-gray-400 max-w-md mx-auto mb-8">
                                Panel de control empresarial completo para gestionar tu equipo y operaciones.
                            </p>

                            {/* Features Grid */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                                <div className="p-6 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                                    <div className="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center mx-auto mb-4">
                                        <Ticket className="w-6 h-6 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <h3 className="font-semibold text-gray-900 dark:text-white mb-2">Todos los Tickets</h3>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">
                                        Supervisa todos los tickets de la empresa
                                    </p>
                                </div>
                                
                                <div className="p-6 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                                    <div className="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mx-auto mb-4">
                                        <UserPlus className="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <h3 className="font-semibold text-gray-900 dark:text-white mb-2">Gestionar Agentes</h3>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">
                                        Administra tu equipo de soporte
                                    </p>
                                </div>
                                
                                <div className="p-6 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                                    <div className="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mx-auto mb-4">
                                        <Bell className="w-6 h-6 text-green-600 dark:text-green-400" />
                                    </div>
                                    <h3 className="font-semibold text-gray-900 dark:text-white mb-2">Anuncios</h3>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">
                                        Comunica novedades a tus usuarios
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
