/**
 * ConfigurePreferences - Paso 2 del Onboarding
 * Permite al usuario configurar sus preferencias de aplicación
 */

import React, { useState, useEffect, FormEvent } from 'react';
import { router } from '@inertiajs/react';
import { useMutation } from '@apollo/client/react';
import { Settings, Globe, Palette, Bell, CheckCircle2 } from 'lucide-react';
import { OnboardingRoute } from '@/Components';
import { OnboardingLayout } from '@/Layouts/Onboarding/OnboardingLayout';
import { Card, Button, Alert, OnboardingFormSkeleton } from '@/Components/ui';
import { useAuth, useNotification, useLocale, useTheme } from '@/contexts';
import { UPDATE_MY_PREFERENCES_MUTATION } from '@/lib/graphql/mutations/users.mutations';
import { MARK_ONBOARDING_COMPLETED_MUTATION } from '@/lib/graphql/mutations/auth.mutations';

export default function ConfigurePreferences() {
    const [progressPercentage, setProgressPercentage] = useState(50); // Empieza en 50% (paso anterior completado)

    return (
        <OnboardingRoute>
            <OnboardingLayout title="Configurar Preferencias">
                <ConfigurePreferencesContent
                    setProgressPercentage={setProgressPercentage}
                    progressPercentage={progressPercentage}
                />
            </OnboardingLayout>
        </OnboardingRoute>
    );
}

function ConfigurePreferencesContent({ 
    setProgressPercentage, 
    progressPercentage 
}: { 
    setProgressPercentage: (value: number) => void;
    progressPercentage: number;
}) {
    const { user, refreshUser, loading: authLoading } = useAuth();
    const { success: showSuccess, error: showError } = useNotification();
    const { locale, t } = useLocale();
    const { themeMode } = useTheme();

    // TODOS LOS HOOKS DEBEN IR ANTES DE CUALQUIER RETURN CONDICIONAL
    // Auto-completar con datos actuales del usuario o valores por defecto del sistema
    const [formData, setFormData] = useState({
        theme: user?.theme || user?.profile?.theme || themeMode || 'light',
        language: user?.language || user?.profile?.language || locale || 'es',
        timezone: user?.profile?.timezone || 'America/La_Paz',
        pushWebNotifications: user?.profile?.pushWebNotifications ?? true,
        notificationsTickets: user?.profile?.notificationsTickets ?? true,
    });
    
    // Estado local para controlar el loading durante toda la operación
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [showSuccessScreen, setShowSuccessScreen] = useState(false);
    const [isProgressComplete, setIsProgressComplete] = useState(false); // Para controlar el color verde
    
    // Mutation para actualizar preferencias
    const [updatePreferences, { loading: mutationLoading }] = useMutation(UPDATE_MY_PREFERENCES_MUTATION);

    // Mutation para marcar onboarding como completado
    const [markOnboardingCompleted, { loading: markingCompleted }] = useMutation(MARK_ONBOARDING_COMPLETED_MUTATION);

    // Actualizar formData cuando user cambie
    useEffect(() => {
        if (user) {
            setFormData(prev => ({
                ...prev,
                theme: user.theme || user.profile?.theme || prev.theme,
                language: user.language || user.profile?.language || prev.language,
                timezone: user.profile?.timezone || prev.timezone,
                pushWebNotifications: user.profile?.pushWebNotifications ?? prev.pushWebNotifications,
                notificationsTickets: user.profile?.notificationsTickets ?? prev.notificationsTickets,
            }));
        }
    }, [user]);

    // AHORA SÍ PODEMOS HACER RETURNS CONDICIONALES
    // Si está cargando la autenticación, mostrar skeleton
    if (authLoading) {
        return <OnboardingFormSkeleton fields={4} columns={1} />;
    }

    const handleSubmit = async (e: FormEvent) => {
        e.preventDefault();

        // Detectar si hay cambios comparando con los datos actuales del usuario
        const currentTheme = user?.theme || user?.profile?.theme || themeMode || 'light';
        const currentLanguage = user?.language || user?.profile?.language || locale || 'es';
        const currentTimezone = user?.profile?.timezone || 'America/La_Paz';
        const currentPushWeb = user?.profile?.pushWebNotifications ?? true;
        const currentNotifTickets = user?.profile?.notificationsTickets ?? true;
        
        const hasChanges = (
            formData.theme !== currentTheme ||
            formData.language !== currentLanguage ||
            formData.timezone !== currentTimezone ||
            formData.pushWebNotifications !== currentPushWeb ||
            formData.notificationsTickets !== currentNotifTickets
        );

        // Si no hay cambios, omitir mutation de preferencias pero SÍ marcar onboarding completado
        if (!hasChanges) {
            console.log('ℹ️ No hay cambios en las preferencias, omitiendo mutation de preferencias...');

            // Activar loading
            setIsSubmitting(true);

            try {
                // Marcar onboarding como completado aunque no se actualicen preferencias
                const onboardingResult = await markOnboardingCompleted();

                if (!onboardingResult.data?.markOnboardingCompleted?.success) {
                    throw new Error('Error al completar onboarding');
                }

                // Simular éxito visual
                setProgressPercentage(100);
                setTimeout(() => setIsProgressComplete(true), 300);
                setTimeout(() => setShowSuccessScreen(true), 1000);

                // Refrescar usuario
                try {
                    const refreshPromise = refreshUser();
                    const timeoutPromise = new Promise((_, reject) =>
                        setTimeout(() => reject(new Error('Refresh timeout')), 5000)
                    );
                    await Promise.race([refreshPromise, timeoutPromise]);
                } catch (refreshError) {
                    console.warn('Refresh user timeout, continuing anyway');
                }

                setTimeout(() => {
                    const roleContexts = user?.roleContexts || [];

                    if (roleContexts.length === 1) {
                        window.location.href = roleContexts[0].dashboardPath;
                    } else if (roleContexts.length > 1) {
                        window.location.href = '/role-selector';
                    } else {
                        window.location.href = '/tickets';
                    }
                }, 3500);
            } catch (error: any) {
                console.error('Error marking onboarding as completed:', error);
                showError(error.message || 'Error al completar onboarding');
                setIsSubmitting(false);
            }
            return;
        }

        // Activar estado de loading local
        setIsSubmitting(true);
        
        // Iniciar animación de progreso simulado mientras espera backend
        // PASO 2: Continuar desde 50% hasta 100%
        let currentProgress = 50; // Empieza desde el 50% del paso anterior
        const progressInterval = setInterval(() => {
            currentProgress += 1;
            if (currentProgress <= 95) { // Detenerse en 95%, el 5% final se completará al recibir respuesta
                setProgressPercentage(currentProgress);
            }
        }, 50); // Incrementar cada 50ms

        try {
            // PASO 1: Actualizar preferencias
            const preferencesResult = await updatePreferences({
                variables: {
                    input: {
                        theme: formData.theme,
                        language: formData.language,
                        timezone: formData.timezone,
                        pushWebNotifications: formData.pushWebNotifications,
                        notificationsTickets: formData.notificationsTickets,
                    },
                },
            });

            if (!preferencesResult.data) {
                throw new Error('Error al actualizar preferencias');
            }

            // PASO 2: Marcar onboarding como completado
            const onboardingResult = await markOnboardingCompleted();

            if (!onboardingResult.data?.markOnboardingCompleted?.success) {
                throw new Error('Error al completar onboarding');
            }

            // Detener el intervalo de progreso
            clearInterval(progressInterval);

            // 1. Completar barra al 100% (azul)
            setProgressPercentage(100);

            // 2. Esperar 300ms, luego cambiar a verde
            setTimeout(() => {
                setIsProgressComplete(true); // Barra se vuelve verde
            }, 300);

            // 3. Actualizar usuario en contexto con los datos del onboarding
            if (onboardingResult.data.markOnboardingCompleted.user && user) {
                // Actualizar el user en AuthContext con los datos más recientes
                const updatedUser = {
                    ...user,
                    ...onboardingResult.data.markOnboardingCompleted.user,
                };
                // Si tienes una función updateUser en el contexto, úsala aquí
                // updateUser(updatedUser);
            }

            // 4. Refrescar datos del usuario
            const refreshPromise = refreshUser();
            const timeoutPromise = new Promise((_, reject) =>
                setTimeout(() => reject(new Error('Refresh timeout')), 5000)
            );

            try {
                await Promise.race([refreshPromise, timeoutPromise]);
            } catch (refreshError) {
                console.warn('Refresh user timeout, continuing anyway');
            }

            // 5. Esperar 1 segundo con barra verde, luego mostrar éxito
            setTimeout(() => {
                setShowSuccessScreen(true);
            }, 1000);

            // 6. Redirigir después de 2.5 segundos adicionales
            setTimeout(() => {
                const roleContexts = user?.roleContexts || [];

                if (roleContexts.length === 1) {
                    // Un solo rol: redirigir directamente
                    window.location.href = roleContexts[0].dashboardPath;
                } else if (roleContexts.length > 1) {
                    // Múltiples roles: mostrar selector
                    window.location.href = '/role-selector';
                } else {
                    // Sin roles: redirigir a /tickets por defecto
                    window.location.href = '/tickets';
                }
            }, 3500);
        } catch (error: any) {
            // Detener el intervalo en caso de error
            clearInterval(progressInterval);
            setProgressPercentage(50); // Volver al 50% (inicio de este paso)

            console.error('Error completing onboarding:', error);
            showError(error.message || 'Error al completar configuración');
            // Desactivar loading solo si hay error
            setIsSubmitting(false);
        }
    };

    const handleSkip = () => {
        // Omitir no envía ninguna mutation, solo redirige
        const roleContexts = user?.roleContexts || [];
        
        if (roleContexts.length === 1) {
            window.location.href = roleContexts[0].dashboardPath;
        } else if (roleContexts.length > 1) {
            window.location.href = '/role-selector';
        } else {
            window.location.href = '/tickets';
        }
    };

    return (
        <div className="max-w-3xl mx-auto opacity-0 animate-[fadeIn_0.8s_ease-out_forwards]">
            {/* Card limpio y espacioso - TODO DENTRO (incluye éxito) */}
            {/* position relative y overflow hidden para que la barra de progreso se pegue al borde */}
            <Card padding="none" className="relative overflow-hidden bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700">
                <div className="p-8 pb-12">
                {/* Mostrar éxito EN EL MISMO CARD */}
                {showSuccessScreen ? (
                    <div className="text-center py-16 animate-[fadeIn_0.4s_ease-out]">
                        {/* Animación draw: círculo + check minimalista (reutilizada) */}
                        <div className="mb-6 flex justify-center">
                            <svg 
                                className="w-16 h-16" 
                                viewBox="0 0 100 100"
                            >
                                {/* Círculo que se dibuja primero */}
                                <circle
                                    cx="50"
                                    cy="50"
                                    r="48"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2.5"
                                    className="text-green-600 dark:text-green-500 animate-drawCircle"
                                />
                                {/* Check que se dibuja después */}
                                <path
                                    d="M30 52 L42 64 L70 36"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2.5"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    className="text-green-600 dark:text-green-500 animate-drawCheck"
                                />
                            </svg>
                        </div>

                        <h1 className="text-2xl font-semibold text-gray-900 dark:text-white mb-2">
                            {t('onboarding.preferences.success')}
                        </h1>
                        
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            {t('onboarding.verify.redirecting')}
                        </p>
                    </div>
                ) : (
                    // Formulario normal
                    <>
                {/* Header dentro del card */}
                <div className="text-center mb-8">
                    <div className="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md text-blue-700 dark:text-blue-300 text-xs font-medium mb-6">
                        <span>{t('onboarding.preferences.step')}</span>
                        <span className="text-blue-300 dark:text-blue-700">•</span>
                        <span>{t('onboarding.preferences.step_label')}</span>
                    </div>
                    
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-3">
                        {t('onboarding.preferences.title')}
                    </h1>
                    <p className="text-gray-600 dark:text-gray-400">
                        {t('onboarding.preferences.subtitle')}
                    </p>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Tema */}
                    <div>
                        <label className="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                            <Palette className="h-5 w-5" />
                            {t('onboarding.preferences.theme')}
                        </label>
                        <div className="grid grid-cols-2 gap-3">
                            <button
                                type="button"
                                onClick={() => setFormData({ ...formData, theme: 'light' })}
                                className={`
                                    p-4 rounded-lg border-2 transition-all
                                    ${formData.theme === 'light'
                                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-blue-300'
                                    }
                                `}
                            >
                                <div className="flex items-center justify-center gap-2">
                                    <span className="text-2xl">☀️</span>
                                    <span className="font-semibold text-gray-900 dark:text-white">{t('onboarding.preferences.theme_light')}</span>
                                    {formData.theme === 'light' ? (
                                        <CheckCircle2 className="h-5 w-5 text-blue-600" />
                                    ) : null}
                                </div>
                            </button>
                            <button
                                type="button"
                                onClick={() => setFormData({ ...formData, theme: 'dark' })}
                                className={`
                                    p-4 rounded-lg border-2 transition-all
                                    ${formData.theme === 'dark'
                                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-blue-300'
                                    }
                                `}
                            >
                                <div className="flex items-center justify-center gap-2">
                                    <span className="text-2xl">🌙</span>
                                    <span className="font-semibold text-gray-900 dark:text-white">{t('onboarding.preferences.theme_dark')}</span>
                                    {formData.theme === 'dark' ? (
                                        <CheckCircle2 className="h-5 w-5 text-blue-600" />
                                    ) : null}
                                </div>
                            </button>
                        </div>
                    </div>

                    {/* Idioma */}
                    <div>
                        <label className="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                            <Globe className="h-5 w-5" />
                            {t('onboarding.preferences.language')}
                        </label>
                        <div className="grid grid-cols-2 gap-3">
                            <button
                                type="button"
                                onClick={() => setFormData({ ...formData, language: 'es' })}
                                className={`
                                    p-4 rounded-lg border-2 transition-all
                                    ${formData.language === 'es'
                                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-blue-300'
                                    }
                                `}
                            >
                                <div className="flex items-center justify-center gap-2">
                                    <span className="text-2xl">🇪🇸</span>
                                    <span className="font-semibold text-gray-900 dark:text-white">{t('onboarding.preferences.language_es')}</span>
                                    {formData.language === 'es' ? (
                                        <CheckCircle2 className="h-5 w-5 text-blue-600" />
                                    ) : null}
                                </div>
                            </button>
                            <button
                                type="button"
                                onClick={() => setFormData({ ...formData, language: 'en' })}
                                className={`
                                    p-4 rounded-lg border-2 transition-all
                                    ${formData.language === 'en'
                                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-blue-300'
                                    }
                                `}
                            >
                                <div className="flex items-center justify-center gap-2">
                                    <span className="text-2xl">🇺🇸</span>
                                    <span className="font-semibold text-gray-900 dark:text-white">{t('onboarding.preferences.language_en')}</span>
                                    {formData.language === 'en' ? (
                                        <CheckCircle2 className="h-5 w-5 text-blue-600" />
                                    ) : null}
                                </div>
                            </button>
                        </div>
                    </div>

                    {/* Zona Horaria */}
                    <div>
                        <label className="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                            🌍 {t('onboarding.preferences.timezone')}
                        </label>
                        <select
                            value={formData.timezone}
                            onChange={(e) => setFormData({ ...formData, timezone: e.target.value })}
                            className="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-800 transition-all"
                        >
                            <option value="America/La_Paz">🇧🇴 La Paz (GMT-4)</option>
                            <option value="America/New_York">🇺🇸 New York (GMT-5)</option>
                            <option value="America/Mexico_City">🇲🇽 Ciudad de México (GMT-6)</option>
                            <option value="America/Bogota">🇨🇴 Bogotá (GMT-5)</option>
                            <option value="America/Argentina/Buenos_Aires">🇦🇷 Buenos Aires (GMT-3)</option>
                            <option value="America/Santiago">🇨🇱 Santiago (GMT-3)</option>
                            <option value="America/Lima">🇵🇪 Lima (GMT-5)</option>
                            <option value="Europe/Madrid">🇪🇸 Madrid (GMT+1)</option>
                        </select>
                    </div>

                    {/* Notificaciones */}
                    <div>
                        <label className="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                            <Bell className="h-5 w-5" />
                            {t('onboarding.preferences.notifications')}
                        </label>
                        <div className="space-y-3">
                            <label className="flex items-center gap-3 p-4 rounded-lg border-2 border-gray-200 dark:border-gray-700 hover:border-blue-300 cursor-pointer transition-all">
                                <input
                                    type="checkbox"
                                    checked={formData.pushWebNotifications}
                                    onChange={(e) => setFormData({ ...formData, pushWebNotifications: e.target.checked })}
                                    className="w-5 h-5 text-blue-600 focus:ring-2 focus:ring-blue-500 rounded"
                                />
                                <div className="flex-1">
                                    <div className="font-semibold text-gray-900 dark:text-white">
                                        {t('onboarding.preferences.web_push')}
                                    </div>
                                </div>
                            </label>

                            <label className="flex items-center gap-3 p-4 rounded-lg border-2 border-gray-200 dark:border-gray-700 hover:border-blue-300 cursor-pointer transition-all">
                                <input
                                    type="checkbox"
                                    checked={formData.notificationsTickets}
                                    onChange={(e) => setFormData({ ...formData, notificationsTickets: e.target.checked })}
                                    className="w-5 h-5 text-blue-600 focus:ring-2 focus:ring-blue-500 rounded"
                                />
                                <div className="flex-1">
                                    <div className="font-semibold text-gray-900 dark:text-white">
                                        {t('onboarding.preferences.ticket_notifications')}
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {/* Separador sutil */}
                    <div className="border-t border-gray-200 dark:border-gray-700 pt-6 mt-8">
                        {/* Botones dentro del card */}
                        <div className="flex items-center justify-between gap-4">
                            <button
                                type="button"
                                onClick={handleSkip}
                                disabled={isSubmitting}
                                className="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 underline underline-offset-4 transition-colors disabled:opacity-50"
                            >
                                {t('onboarding.preferences.skip')}
                            </button>
                            
                            <Button
                                type="submit"
                                isLoading={isSubmitting || mutationLoading || markingCompleted}
                                disabled={isSubmitting || mutationLoading || markingCompleted}
                                className="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition-all"
                            >
                                {(isSubmitting || markingCompleted) ? t('onboarding.preferences.completing') : t('onboarding.preferences.continue')}
                            </Button>
                        </div>
                        
                        {/* Hint discreto */}
                        <p className="text-xs text-center text-gray-500 dark:text-gray-500 mt-4">
                            {t('onboarding.preferences.hint')}
                        </p>
                    </div>
                </form>
                    </>
                )}
                </div>
                
                {/* Barra de progreso DENTRO del Card, pegada al borde inferior */}
                <div className="absolute bottom-0 left-0 right-0 h-1">
                    <div 
                        className={`h-full transition-all duration-500 ease-out ${
                            isProgressComplete
                                ? 'bg-gradient-to-r from-green-500 to-emerald-600' 
                                : 'bg-gradient-to-r from-blue-500 to-blue-600'
                        }`}
                        style={{ 
                            width: `${progressPercentage}%`,
                            transformOrigin: 'left'
                        }}
                    />
                </div>
            </Card>
        </div>
    );
}
