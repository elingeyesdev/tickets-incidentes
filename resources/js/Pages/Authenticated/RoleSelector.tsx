import { useState, useEffect } from 'react';
import {
    User,
    Briefcase,
    Shield,
    ShieldCheck,
    Building,
    Sparkles
} from 'lucide-react';
import { AuthGuard } from '@/components/Auth/AuthGuard';
import { OnboardingLayout } from '@/Layouts/Onboarding/OnboardingLayout';
import { useAuth } from '@/contexts';
import type { RoleContext } from '@/types/graphql';

function RoleSelectorContent() {
    const { user, loading: authLoading, selectRole } = useAuth();
    
    const [roleContexts, setRoleContexts] = useState<RoleContext[]>([]);
    const [selectedRole, setSelectedRole] = useState<string | null>(null);
    const [isRedirecting, setIsRedirecting] = useState(false);

    useEffect(() => {
        if (user && user.roleContexts) {
            setRoleContexts(user.roleContexts);
            
            // AuthGuard now handles redirection for single-role users, but this is a good fallback.
            if (user.roleContexts.length === 1) {
                handleRoleSelection(user.roleContexts[0]);
            }
        }
    }, [user]);

    const handleRoleSelection = (role: RoleContext) => {
        setIsRedirecting(true);
        setSelectedRole(role.roleCode);

        // Use the centralized selectRole function from AuthContext
        // It handles state update, persistence, and redirection.
        selectRole(role.roleCode);
    };

    const getRoleIcon = (roleCode: string) => {
        const icons: Record<string, React.ReactNode> = {
            'USER': <User className="h-8 w-8" />,
            'AGENT': <Briefcase className="h-8 w-8" />,
            'COMPANY_ADMIN': <Shield className="h-8 w-8" />,
            'PLATFORM_ADMIN': <ShieldCheck className="h-8 w-8" />,
        };
        return icons[roleCode] || <User className="h-8 w-8" />;
    };

    const getRoleGradient = (roleCode: string) => {
        const gradients: Record<string, string> = {
            'USER': 'from-blue-500 via-blue-600 to-indigo-600',
            'AGENT': 'from-green-500 via-emerald-600 to-teal-600',
            'COMPANY_ADMIN': 'from-purple-500 via-violet-600 to-purple-700',
            'PLATFORM_ADMIN': 'from-red-500 via-rose-600 to-pink-600',
        };
        return gradients[roleCode] || 'from-gray-500 to-gray-600';
    };

    const getRoleHoverGlow = (roleCode: string) => {
        const glows: Record<string, string> = {
            'USER': 'hover:shadow-blue-500/50',
            'AGENT': 'hover:shadow-green-500/50',
            'COMPANY_ADMIN': 'hover:shadow-purple-500/50',
            'PLATFORM_ADMIN': 'hover:shadow-red-500/50',
        };
        return glows[roleCode] || 'hover:shadow-gray-500/50';
    };

    if (authLoading || !user) {
        return (
            <div className="flex items-center justify-center min-h-[400px]">
                <div className="text-center">
                    <div className="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                    <p className="mt-4 text-gray-600 dark:text-gray-400">Cargando...</p>
                </div>
            </div>
        );
    }

    if (roleContexts.length === 0) {
        return (
            <div className="max-w-md mx-auto text-center">
                <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
                    <div className="mb-6">
                        <div className="mx-auto w-20 h-20 bg-yellow-100 dark:bg-yellow-900/20 rounded-full flex items-center justify-center">
                            <Shield className="h-10 w-10 text-yellow-600 dark:text-yellow-500" />
                        </div>
                    </div>
                    <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                        Sin Roles Asignados
                    </h2>
                    <p className="text-gray-600 dark:text-gray-400 mb-6">
                        Tu cuenta no tiene roles asignados actualmente. Contacta al administrador.
                    </p>
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                        Puedes cerrar sesión usando el botón en la esquina superior derecha.
                    </p>
                </div>
            </div>
        );
    }

    return (
        <div className="w-full max-w-5xl mx-auto">
            <div className="text-center mb-10 opacity-0 animate-[fadeIn_0.6s_ease-out_forwards]">
                <div className="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg shadow-sm mb-8">
                    <Sparkles className="h-5 w-5" />
                    <span className="text-sm font-semibold tracking-wide">
                        ¡Bienvenido de vuelta, {user.displayName?.split(' ')[0] || 'Usuario'}!
                    </span>
                </div>
                
                <h1 className="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4 tracking-tight">
                    Selecciona tu Rol de Trabajo
                </h1>
                
                <p className="text-base text-gray-600 dark:text-gray-400 max-w-md mx-auto">
                    Elige el perfil con el que deseas acceder hoy
                </p>
            </div>

            <div className="grid grid-cols-1 gap-6 mb-8 max-w-3xl mx-auto">
                {roleContexts.map((role, index) => (
                    <button
                        key={`${role.roleCode}-${role.company?.id || 'global'}-${index}`}
                        onClick={() => !isRedirecting && handleRoleSelection(role)}
                        disabled={isRedirecting && selectedRole === role.roleCode}
                        style={{ animationDelay: `${index * 150}ms` }}
                        className={`
                            group relative overflow-hidden
                            bg-white dark:bg-gray-800 
                            rounded-xl shadow-md hover:shadow-xl
                            border border-gray-200 dark:border-gray-700
                            ${getRoleHoverGlow(role.roleCode)}
                            transition-all duration-300 
                            hover:scale-[1.01] hover:-translate-y-0.5
                            disabled:opacity-70 disabled:cursor-not-allowed
                            ${selectedRole === role.roleCode ? 'ring-2 ring-blue-600 ring-offset-2 dark:ring-offset-gray-900 border-transparent' : ''}
                            opacity-0 animate-[slideInLeft_0.5s_ease-out_forwards]
                            p-5 text-left
                        `}
                    >
                        <div className={`
                            absolute top-0 right-0 w-48 h-48 bg-gradient-to-br ${getRoleGradient(role.roleCode)}
                            opacity-10 rounded-full blur-3xl group-hover:opacity-20 transition-opacity
                            -mr-24 -mt-24
                        `} />

                        <div className="relative flex items-start gap-4">
                            <div className={`
                                flex-shrink-0 w-14 h-14 rounded-lg 
                                bg-gradient-to-br ${getRoleGradient(role.roleCode)}
                                flex items-center justify-center text-white shadow-md
                                group-hover:scale-105 transition-transform duration-300
                            `}>
                                {getRoleIcon(role.roleCode)}
                            </div>

                            <div className="flex-1 min-w-0">
                                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-1 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                    {role.roleName}
                                </h3>
                                
                                {role.company && (
                                    <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-2">
                                        <Building className="h-4 w-4 flex-shrink-0" />
                                        <span className="truncate font-medium">{role.company.name}</span>
                                    </div>
                                )}

                                <p className="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                                    {role.roleCode === 'USER' && 'Crea y gestiona tus tickets de soporte con facilidad'}
                                    {role.roleCode === 'AGENT' && 'Atiende tickets y brinda soporte a usuarios'}
                                    {role.roleCode === 'COMPANY_ADMIN' && 'Administra tu empresa, equipo y configuraciones'}
                                    {role.roleCode === 'PLATFORM_ADMIN' && 'Control total sobre la plataforma y todas las empresas'}
                                </p>

                                {isRedirecting && selectedRole === role.roleCode && (
                                    <div className="mt-3 flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400 font-medium">
                                        <div className="animate-spin rounded-full h-4 w-4 border-2 border-blue-600 border-t-transparent"></div>
                                        Redirigiendo al dashboard...
                                    </div>
                                )}
                            </div>

                            <div className="flex-shrink-0 text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 group-hover:translate-x-1 transition-all">
                                <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </button>
                ))}
            </div>

            <div className="text-center opacity-0 animate-[fadeIn_0.6s_ease-out_0.6s_forwards]">
                <p className="text-sm text-gray-500 dark:text-gray-400">
                    💡 Puedes cambiar de rol en cualquier momento desde el menú de usuario
                </p>
            </div>
        </div>
    );
}

export default function RoleSelector() {
    return (
        <AuthGuard>
            <OnboardingLayout title="Seleccionar Rol">
                <RoleSelectorContent />
            </OnboardingLayout>
        </AuthGuard>
    );
}
