/**
 * Unauthorized Page - Error 403
 * Se muestra cuando un usuario autenticado intenta acceder a una ruta sin permisos
 */

import { Head, Link } from '@inertiajs/react';
import { PublicLayout } from '@/Layouts/Public/PublicLayout';
import { ShieldX, Home, ArrowLeft } from 'lucide-react';
import { Button } from '@/Components/ui';
import { useAuth } from '@/contexts';
import { getDefaultDashboard } from '@/config/permissions';
import type { RoleCode } from '@/types';

export default function Unauthorized() {
    const { user } = useAuth();

    // Determinar URL de retorno según el usuario
    const getReturnUrl = () => {
        if (!user) return '/login';

        const userRoles = user.roleContexts.map((rc) => rc.roleCode as RoleCode);
        return getDefaultDashboard(userRoles);
    };

    return (
        <PublicLayout title="Acceso Denegado" showNavbar={false} showFooter={false}>
            <Head title="403 - Acceso Denegado" />

            <div className="min-h-screen flex items-center justify-center px-4">
                <div className="max-w-md w-full text-center">
                    {/* Icon */}
                    <div className="mb-8 flex justify-center">
                        <div className="relative">
                            {/* Círculo de fondo con animación */}
                            <div className="absolute inset-0 bg-red-100 dark:bg-red-900/20 rounded-full animate-pulse"></div>

                            {/* Icono principal */}
                            <div className="relative bg-white dark:bg-gray-800 rounded-full p-8 shadow-lg">
                                <ShieldX className="h-24 w-24 text-red-600 dark:text-red-400" />
                            </div>
                        </div>
                    </div>

                    {/* Título */}
                    <h1 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                        Acceso Denegado
                    </h1>

                    {/* Código de error */}
                    <div className="inline-block px-4 py-2 bg-red-100 dark:bg-red-900/20 rounded-lg mb-6">
                        <span className="text-sm font-mono text-red-600 dark:text-red-400">
                            Error 403 - Forbidden
                        </span>
                    </div>

                    {/* Mensaje */}
                    <p className="text-lg text-gray-600 dark:text-gray-400 mb-8">
                        No tienes permisos para acceder a esta página.
                        {user && (
                            <>
                                <br />
                                <span className="text-sm">
                                    Tu rol actual no tiene acceso a este recurso.
                                </span>
                            </>
                        )}
                    </p>

                    {/* Acciones */}
                    <div className="space-y-3">
                        <Link href={getReturnUrl()}>
                            <Button className="w-full" size="lg">
                                <Home className="mr-2 h-5 w-5" />
                                Ir a mi Dashboard
                            </Button>
                        </Link>

                        <button
                            onClick={() => window.history.back()}
                            className="w-full flex items-center justify-center gap-2 px-6 py-3 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors"
                        >
                            <ArrowLeft className="h-5 w-5" />
                            Regresar
                        </button>
                    </div>

                    {/* Info adicional */}
                    {user && (
                        <div className="mt-8 p-4 bg-blue-50 dark:bg-blue-900/10 rounded-lg border border-blue-200 dark:border-blue-800">
                            <p className="text-sm text-blue-800 dark:text-blue-300">
                                Si crees que deberías tener acceso a esta página, contacta al administrador.
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </PublicLayout>
    );
}
