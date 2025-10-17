/**
 * Agent Dashboard - Dashboard para agentes de soporte
 * Permite gestionar y responder tickets asignados
 */

import { Head } from '@inertiajs/react';
import { ProtectedRoute } from '@/Components/guards';
import { AgentLayout } from '@/Layouts/Agent/AgentLayout';
import { Card } from '@/Components/ui';
import { Inbox, Clock, CheckCircle2, TrendingUp } from 'lucide-react';

export default function AgentDashboard() {
    return (
        <ProtectedRoute allowedRoles={['AGENT']}>
            <AgentLayout title="Dashboard del Agente">
                <Head title="Dashboard - Agente" />

                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        Panel de Agente
                    </h1>
                    <p className="text-gray-600 dark:text-gray-400">
                        Gestiona tickets y brinda soporte a usuarios
                    </p>
                </div>

                {/* Stats Grid */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <Card>
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-green-100 dark:bg-green-900/20 rounded-lg">
                                <Inbox className="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-600 dark:text-gray-400">Asignados</p>
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
                                <p className="text-sm text-gray-600 dark:text-gray-400">En Progreso</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">0</p>
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
                                <CheckCircle2 className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-600 dark:text-gray-400">Resueltos Hoy</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">0</p>
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-purple-100 dark:bg-purple-900/20 rounded-lg">
                                <TrendingUp className="h-6 w-6 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-600 dark:text-gray-400">Calificación</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">-</p>
                            </div>
                        </div>
                    </Card>
                </div>

                {/* Placeholder para contenido futuro */}
                <Card>
                    <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        Tickets Recientes
                    </h2>
                    <p className="text-gray-600 dark:text-gray-400">
                        No hay tickets asignados actualmente.
                    </p>
                </Card>
            </AgentLayout>
        </ProtectedRoute>
    );
}
