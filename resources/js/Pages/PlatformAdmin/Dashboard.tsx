/**
 * Platform Admin Dashboard - Dashboard para rol PLATFORM_ADMIN
 */

import { DashboardLayout } from '@/Layouts/DashboardLayout';
import { useAuth } from '@/contexts';
import { ShieldCheck, Building, Users, Ticket, Settings, Sparkles } from 'lucide-react';

export default function Dashboard() {
    const { user } = useAuth();

    return (
        <DashboardLayout title="Dashboard - Administrador de Plataforma">
            <div className="p-8">
                {/* Welcome Header */}
                <div className="mb-8">
                    <div className="flex items-center gap-2 mb-2">
                        <Sparkles className="h-5 w-5 text-red-600 dark:text-red-400" />
                        <span className="text-sm font-medium text-red-600 dark:text-red-400">
                            Dashboard de Administrador de Plataforma
                        </span>
                    </div>
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                        ¡Bienvenido, {user?.displayName || user?.profile?.firstName || 'Administrador'}!
                    </h1>
                    <p className="text-gray-600 dark:text-gray-400 mt-1">
                        Control total sobre la plataforma y todas las empresas
                    </p>
                </div>

                {/* Coming Soon Section */}
                <div className="max-w-4xl">
                    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12">
                        <div className="text-center">
                            <div className="w-20 h-20 mx-auto mb-6 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                                <ShieldCheck className="w-10 h-10 text-red-600 dark:text-red-400" />
                            </div>
                            
                            <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                                Próximamente
                            </h2>
                            
                            <p className="text-gray-600 dark:text-gray-400 max-w-md mx-auto mb-8">
                                Panel de administración centralizado para supervisar toda la plataforma.
                            </p>

                            {/* Features Grid */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                                <div className="p-6 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                                    <div className="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center mx-auto mb-4">
                                        <Building className="w-6 h-6 text-red-600 dark:text-red-400" />
                                    </div>
                                    <h3 className="font-semibold text-gray-900 dark:text-white mb-2">Gestionar Empresas</h3>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">
                                        Aprueba y administra empresas
                                    </p>
                                </div>
                                
                                <div className="p-6 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                                    <div className="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mx-auto mb-4">
                                        <Users className="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <h3 className="font-semibold text-gray-900 dark:text-white mb-2">Todos los Usuarios</h3>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">
                                        Supervisa la base de usuarios
                                    </p>
                                </div>
                                
                                <div className="p-6 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                                    <div className="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center mx-auto mb-4">
                                        <Settings className="w-6 h-6 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <h3 className="font-semibold text-gray-900 dark:text-white mb-2">Configuración del Sistema</h3>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">
                                        Ajustes globales de la plataforma
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
