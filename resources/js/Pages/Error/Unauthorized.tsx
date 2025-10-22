import { Head, router } from '@inertiajs/react';
import { Button } from '@/Components/ui';
import { ShieldOff } from 'lucide-react';

export default function Unauthorized() {
    return (
        <>
            <Head title="403 - No Autorizado" />
            <div className="min-h-screen bg-gray-100 dark:bg-gray-900 flex flex-col items-center justify-center">
                <div className="text-center p-8 max-w-md">
                    <ShieldOff className="w-24 h-24 text-red-500 mx-auto mb-6" />
                    <h1 className="text-5xl font-extrabold text-gray-900 dark:text-white mb-4">
                        403
                    </h1>
                    <h2 className="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-4">
                        Acceso Denegado
                    </h2>
                    <p className="text-gray-600 dark:text-gray-400 mb-8">
                        No tienes los permisos necesarios para acceder a esta página. Si crees que esto es un error, por favor, contacta al administrador.
                    </p>
                    <div className="flex gap-4 justify-center">
                        <Button variant="outline" onClick={() => window.history.back()}>
                            Volver Atrás
                        </Button>
                        <Button onClick={() => router.visit('/dashboard')}>
                            Ir al Dashboard
                        </Button>
                    </div>
                </div>
            </div>
        </>
    );
}
