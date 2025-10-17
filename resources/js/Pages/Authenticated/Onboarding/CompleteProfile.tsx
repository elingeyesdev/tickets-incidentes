/**
 * CompleteProfile - Paso 1 del Onboarding
 * Permite al usuario completar/actualizar su perfil
 */

import React, { useState, useEffect, FormEvent } from 'react';
import { router } from '@inertiajs/react';
import { useMutation } from '@apollo/client/react';
import { User, Phone, CheckCircle2, AlertCircle } from 'lucide-react';
import { OnboardingRoute } from '@/Components';
import { OnboardingLayout } from '@/Layouts/Onboarding/OnboardingLayout';
import { Card, Button, Input, Alert, OnboardingFormSkeleton } from '@/Components/ui';
import { useAuth, useNotification, useLocale } from '@/contexts';
import { UPDATE_MY_PROFILE_MUTATION } from '@/lib/graphql/mutations/users.mutations';
import type { UpdateMyProfileMutation, UpdateMyProfileMutationVariables, UpdateProfileInput } from '@/types/graphql-generated';

export default function CompleteProfile() {
    const [progressPercentage, setProgressPercentage] = useState(0);

    return (
        <OnboardingRoute>
            <OnboardingLayout title="Completar Perfil">
                <CompleteProfileContent
                    setProgressPercentage={setProgressPercentage}
                    progressPercentage={progressPercentage}
                />
            </OnboardingLayout>
        </OnboardingRoute>
    );
}

function CompleteProfileContent({ 
    setProgressPercentage, 
    progressPercentage 
}: { 
    setProgressPercentage: (value: number) => void;
    progressPercentage: number;
}) {
    const { user, refreshUser, loading: authLoading } = useAuth();
    const { success: showSuccess, error: showError } = useNotification();
    const { t } = useLocale();

    // TODOS LOS HOOKS DEBEN IR ANTES DE CUALQUIER RETURN CONDICIONAL
    // Auto-completar con datos del registro (firstName, lastName ya vienen del backend)
    const [formData, setFormData] = useState({
        firstName: user?.profile?.firstName || (user?.displayName?.split(' ')[0]) || '',
        lastName: user?.profile?.lastName || (user?.displayName?.split(' ').slice(1).join(' ')) || '',
        phoneNumber: user?.profile?.phoneNumber || '',
        countryCode: '+591', // Bolivia por defecto
    });
    
    const [touched, setTouched] = useState({
        firstName: false,
        lastName: false,
        phoneNumber: false,
    });

    // Estado local para controlar el loading durante toda la operación
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Mutation para actualizar perfil
    const [updateProfile, { loading: mutationLoading }] = useMutation<UpdateMyProfileMutation, UpdateMyProfileMutationVariables>(UPDATE_MY_PROFILE_MUTATION);
    
    // Actualizar formData cuando user cambie (útil si refreshUser trae nuevos datos)
    useEffect(() => {
        if (user) {
            setFormData(prev => ({
                ...prev,
                firstName: user.profile?.firstName || (user.displayName?.split(' ')[0]) || prev.firstName,
                lastName: user.profile?.lastName || (user.displayName?.split(' ').slice(1).join(' ')) || prev.lastName,
                phoneNumber: user.profile?.phoneNumber || prev.phoneNumber,
            }));
        }
    }, [user]);

    // AHORA SÍ PODEMOS HACER RETURNS CONDICIONALES
    // Si está cargando la autenticación, mostrar skeleton
    if (authLoading) {
        return <OnboardingFormSkeleton fields={2} columns={2} />;
    }

    // Validación en tiempo real
    const validation = {
        firstName: {
            valid: formData.firstName.length >= 2 && formData.firstName.length <= 100,
            message: formData.firstName.length === 0 
                ? 'El nombre es requerido' 
                : formData.firstName.length < 2 
                ? 'Mínimo 2 caracteres' 
                : 'Máximo 100 caracteres'
        },
        lastName: {
            valid: formData.lastName.length >= 2 && formData.lastName.length <= 100,
            message: formData.lastName.length === 0 
                ? 'El apellido es requerido' 
                : formData.lastName.length < 2 
                ? 'Mínimo 2 caracteres' 
                : 'Máximo 100 caracteres'
        },
        phoneNumber: {
            valid: formData.phoneNumber === '' || (formData.phoneNumber.length >= 7 && formData.phoneNumber.length <= 15),
            message: formData.phoneNumber.length < 7 
                ? 'Mínimo 7 dígitos' 
                : 'Máximo 15 dígitos'
        },
    };

    const isFormValid =
        validation.firstName.valid &&
        validation.lastName.valid &&
        validation.phoneNumber.valid;

    const handleSubmit = async (e: FormEvent) => {
        e.preventDefault();

        if (!isFormValid) {
            setTouched({
                firstName: true,
                lastName: true,
                phoneNumber: true,
            });
            showError('Por favor, corrige los errores en el formulario');
            return;
        }

        // Detectar si hay cambios comparando con los datos actuales del usuario
        const currentFirstName = user?.profile?.firstName || (user?.displayName?.split(' ')[0]) || '';
        const currentLastName = user?.profile?.lastName || (user?.displayName?.split(' ').slice(1).join(' ')) || '';
        const currentPhone = user?.profile?.phoneNumber || '';
        
        const newPhone = formData.phoneNumber ? `${formData.countryCode}${formData.phoneNumber.trim()}` : '';
        
        const hasChanges = (
            formData.firstName.trim() !== currentFirstName ||
            formData.lastName.trim() !== currentLastName ||
            newPhone !== currentPhone
        );

        // Si no hay cambios, omitir mutation y continuar al siguiente paso
        if (!hasChanges) {
            console.log('ℹ️ No hay cambios en el perfil, omitiendo mutation...');
            router.visit('/onboarding/preferences');
            return;
        }

        // Activar estado de loading local
        setIsSubmitting(true);
        
        // Iniciar animación de progreso simulado mientras espera backend
        // PASO 1: Llenar hasta 50% (mitad del flujo completo)
        let currentProgress = 0;
        const progressInterval = setInterval(() => {
            currentProgress += 1; // Más lento para llegar a 45%
            if (currentProgress <= 45) { // Detenerse en 45%, el 5% final se completará al recibir respuesta
                setProgressPercentage(currentProgress);
            }
        }, 50); // Incrementar cada 50ms

        try {
            // Construir input dinámicamente - solo enviar campos que tienen valor
            const input: UpdateProfileInput = {
                firstName: formData.firstName.trim(),
                lastName: formData.lastName.trim(),
            };

            // Solo agregar phoneNumber si tiene valor
            if (formData.phoneNumber && formData.phoneNumber.trim()) {
                input.phoneNumber = `${formData.countryCode}${formData.phoneNumber.trim()}`;
            }

            const result = await updateProfile({
                variables: { input },
            });

            // Detener el intervalo de progreso
            clearInterval(progressInterval);

            if (result.data) {
                // Completar hasta 50% (mitad del flujo)
                setProgressPercentage(50);
                
                showSuccess('✅ Perfil actualizado correctamente');
                
                // Refrescar datos del usuario con timeout para evitar cuelgues
                const refreshPromise = refreshUser();
                const timeoutPromise = new Promise((_, reject) => 
                    setTimeout(() => reject(new Error('Refresh timeout')), 5000)
                );
                
                try {
                    await Promise.race([refreshPromise, timeoutPromise]);
                } catch (refreshError) {
                    console.warn('Refresh user timeout, continuing anyway');
                }
                
                // Redirigir al siguiente paso después de mostrar la barra en 50%
                setTimeout(() => {
                    router.visit('/onboarding/preferences');
                }, 800);
            }
        } catch (error) {
            // Detener el intervalo en caso de error
            clearInterval(progressInterval);
            setProgressPercentage(0); // Resetear barra

            console.error('Error updating profile:', error);
            const errorMessage = error instanceof Error ? error.message : 'Error al actualizar el perfil';
            showError(errorMessage);
            // Desactivar loading solo si hay error
            setIsSubmitting(false);
        }
    };

    const handleSkip = () => {
        // Omitir no envía ninguna mutation
        router.visit('/onboarding/preferences');
    };

    return (
        <div className="max-w-3xl mx-auto opacity-0 animate-[fadeIn_0.8s_ease-out_forwards]">
            {/* Card limpio y espacioso - TODO DENTRO */}
            {/* position relative y overflow hidden para que la barra de progreso se pegue al borde */}
            <Card padding="none" className="relative overflow-hidden bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700">
                <div className="p-8 pb-12">
                {/* Header dentro del card */}
                <div className="text-center mb-8">
                    <div className="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md text-blue-700 dark:text-blue-300 text-xs font-medium mb-6">
                        <span>{t('onboarding.profile.step')}</span>
                        <span className="text-blue-300 dark:text-blue-700">•</span>
                        <span>{t('onboarding.profile.step_label')}</span>
                    </div>
                    
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-3">
                        {t('onboarding.profile.title')}
                    </h1>
                    <p className="text-gray-600 dark:text-gray-400">
                        {t('onboarding.profile.subtitle')}
                    </p>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Nombre y Apellido */}
                    <div className="grid grid-cols-2 gap-4">
                        <Input
                            label={t('onboarding.profile.first_name') + ' *'}
                            value={formData.firstName}
                            onChange={(e) => setFormData({ ...formData, firstName: e.target.value })}
                            onBlur={() => setTouched({ ...touched, firstName: true })}
                            required
                            placeholder={t('onboarding.profile.first_name_placeholder')}
                            leftIcon={<User className="h-5 w-5" />}
                            rightIcon={
                                touched.firstName && formData.firstName ? (
                                    validation.firstName.valid ? (
                                        <CheckCircle2 className="h-5 w-5 text-green-500" />
                                    ) : (
                                        <AlertCircle className="h-5 w-5 text-red-500" />
                                    )
                                ) : null
                            }
                            error={
                                touched.firstName && !validation.firstName.valid
                                    ? validation.firstName.message
                                    : undefined
                            }
                        />

                        <Input
                            label={t('onboarding.profile.last_name') + ' *'}
                            value={formData.lastName}
                            onChange={(e) => setFormData({ ...formData, lastName: e.target.value })}
                            onBlur={() => setTouched({ ...touched, lastName: true })}
                            required
                            placeholder={t('onboarding.profile.last_name_placeholder')}
                            leftIcon={<User className="h-5 w-5" />}
                            rightIcon={
                                touched.lastName && formData.lastName ? (
                                    validation.lastName.valid ? (
                                        <CheckCircle2 className="h-5 w-5 text-green-500" />
                                    ) : (
                                        <AlertCircle className="h-5 w-5 text-red-500" />
                                    )
                                ) : null
                            }
                            error={
                                touched.lastName && !validation.lastName.valid
                                    ? validation.lastName.message
                                    : undefined
                            }
                        />
                    </div>

                    {/* Teléfono con Selector de País */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {t('onboarding.profile.phone')}
                        </label>
                        <div className="flex gap-2">
                            {/* Country Code Selector */}
                            <select
                                value={formData.countryCode}
                                onChange={(e) => setFormData({ ...formData, countryCode: e.target.value })}
                                className="w-32 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="+591">🇧🇴 +591</option>
                                <option value="+1">🇺🇸 +1</option>
                                <option value="+52">🇲🇽 +52</option>
                                <option value="+57">🇨🇴 +57</option>
                                <option value="+54">🇦🇷 +54</option>
                                <option value="+56">🇨🇱 +56</option>
                                <option value="+51">🇵🇪 +51</option>
                                <option value="+34">🇪🇸 +34</option>
                            </select>

                            {/* Phone Number Input */}
                            <div className="flex-1">
                                <Input
                                    value={formData.phoneNumber}
                                    onChange={(e) => setFormData({ ...formData, phoneNumber: e.target.value.replace(/[^0-9]/g, '') })}
                                    onBlur={() => setTouched({ ...touched, phoneNumber: true })}
                                    placeholder={t('onboarding.profile.phone_placeholder')}
                                    leftIcon={<Phone className="h-5 w-5" />}
                                    rightIcon={
                                        touched.phoneNumber && formData.phoneNumber ? (
                                            validation.phoneNumber.valid ? (
                                                <CheckCircle2 className="h-5 w-5 text-green-500" />
                                            ) : (
                                                <AlertCircle className="h-5 w-5 text-red-500" />
                                            )
                                        ) : null
                                    }
                                    error={
                                        touched.phoneNumber && !validation.phoneNumber.valid
                                            ? validation.phoneNumber.message
                                            : undefined
                                    }
                                />
                            </div>
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
                                {t('onboarding.profile.skip')}
                            </button>
                            
                            <Button
                                type="submit"
                                isLoading={isSubmitting}
                                disabled={!isFormValid || isSubmitting}
                                className="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition-all"
                            >
                                {isSubmitting ? t('onboarding.profile.saving') : t('onboarding.profile.continue')}
                            </Button>
                        </div>
                        
                        {/* Hint discreto */}
                        <p className="text-xs text-center text-gray-500 dark:text-gray-500 mt-4">
                            {t('onboarding.profile.hint')}
                        </p>
                    </div>
                </form>
                </div>
                
                {/* Barra de progreso DENTRO del Card, pegada al borde inferior */}
                <div className="absolute bottom-0 left-0 right-0 h-1">
                    <div 
                        className="h-full bg-gradient-to-r from-blue-500 to-blue-600 transition-all duration-500 ease-out"
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
