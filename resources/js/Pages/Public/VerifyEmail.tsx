/**
 * VerifyEmail Page - Verificación de Email
 */

import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { useMutation } from '@apollo/client/react';
import { OnboardingLayout } from '@/Layouts/Onboarding/OnboardingLayout';
import { Card, Button, Alert } from '@/Components/ui';
import { useAuth, useLocale } from '@/contexts';
import { VERIFY_EMAIL_MUTATION, RESEND_VERIFICATION_MUTATION } from '@/lib/graphql/mutations/auth.mutations';
import { AlertTriangle } from 'lucide-react';

interface VerifyEmailPageProps {
    token?: string;
}

function VerifyEmailContent({ token }: VerifyEmailPageProps) {
    const { user, refreshUser } = useAuth();
    const { t } = useLocale();
    
    // Extract token from URL query params if not provided as prop
    const [urlToken, setUrlToken] = useState<string | null>(token || null);
    
    useEffect(() => {
        if (typeof window !== 'undefined' && !token) {
            const params = new URLSearchParams(window.location.search);
            const queryToken = params.get('token');
            if (queryToken) {
                setUrlToken(queryToken);
                console.log('🔑 Token extracted from URL');
            }
        }
    }, [token]);
    const [verificationStatus, setVerificationStatus] = useState<'pending' | 'success' | 'error'>('pending');
    const [message, setMessage] = useState<string>('');
    const [canResend, setCanResend] = useState(false); // Empieza deshabilitado
    const [showSkipWarning, setShowSkipWarning] = useState(false);
    const [resendCount, setResendCount] = useState(1); // Comienza en 1
    const [countdown, setCountdown] = useState(60); // Empieza con 60s de cooldown

    const [verifyEmail] = useMutation(VERIFY_EMAIL_MUTATION, {
        onCompleted: (data: any) => {
            if (data.verifyEmail.success) {
                setVerificationStatus('success');
                setMessage(data.verifyEmail.message || 'Email verificado exitosamente');
                // Refrescar usuario para actualizar emailVerified
                refreshUser();
                // Redirigir al onboarding después de 3 segundos
                setTimeout(() => {
                    router.visit('/onboarding/profile');
                }, 3000);
            } else {
                setVerificationStatus('error');
                setMessage(data.verifyEmail.message || 'Error al verificar email');
                setCanResend(data.verifyEmail.canResend);
            }
        },
        onError: (error) => {
            setVerificationStatus('error');
            setMessage(error.message || 'Error al verificar email');
        },
    });

    const [resendVerification, { loading: resending }] = useMutation(RESEND_VERIFICATION_MUTATION, {
        onCompleted: (data: any) => {
            if (data.resendVerification.success) {
                setMessage(data.resendVerification.message || t('onboarding.verify.resend_success'));
                setCanResend(false);
                setResendCount(prev => prev + 1);
                setCountdown(60);
                // Email reenviado exitosamente
            } else {
                setMessage(data.resendVerification.message || t('onboarding.verify.resend_error'));
            }
        },
        onError: (error) => {
            setMessage(error.message || t('onboarding.verify.resend_error'));
        },
    });

    // Countdown timer
    useEffect(() => {
        if (countdown > 0) {
            const timer = setTimeout(() => {
                setCountdown(countdown - 1);
            }, 1000);
            return () => clearTimeout(timer);
        } else if (countdown === 0 && !canResend) {
            setCanResend(true);
        }
        return undefined;
    }, [countdown, canResend]);

    // Si hay token en URL, verificar automáticamente SOLO si es de un link de email
    // (token presente en URL, no es el usuario que viene de registro directo)
    useEffect(() => {
        if (urlToken && urlToken.length > 10) {
            console.log('🔑 Token de email detectado, verificando automáticamente...');
            // Pequeño delay para que el usuario vea la pantalla
            const timer = setTimeout(() => {
                verifyEmail({ variables: { token: urlToken } });
            }, 500);
            return () => clearTimeout(timer);
        }
        return undefined;
    }, [urlToken]);
    
    // Auto-cerrar pestaña después de verificación exitosa (si se abrió desde email)
    useEffect(() => {
        if (verificationStatus === 'success' && urlToken && window.opener) {
            // Esta pestaña fue abierta desde un email, cerrarla automáticamente después de 3 segundos
            console.log('✅ Verificación exitosa, cerrando pestaña en 3 segundos...');
            setTimeout(() => {
                window.close();
            }, 3000);
        }
    }, [verificationStatus, urlToken]);

    const handleResend = () => {
        resendVerification();
    };

    const handleSkip = () => {
        // Para usuarios NUEVOS: ir a onboarding
        // Para usuarios existentes: ir a su dashboard según roles
        
        if (!user || !user.roleContexts || user.roleContexts.length === 0) {
            // Sin roles o sin usuario, ir a onboarding (es un usuario nuevo)
            router.visit('/onboarding/profile', { replace: true });
            return;
        }

        // Verificar si el usuario ya completó el onboarding
        const hasCompletedOnboarding = user.onboardingCompletedAt !== null && user.onboardingCompletedAt !== undefined;
        
        if (!hasCompletedOnboarding) {
            // Usuario nuevo, ir a onboarding
            router.visit('/onboarding/profile', { replace: true });
        } else {
            // Usuario existente que ya completó onboarding, ir a dashboard
            if (user.roleContexts.length === 1) {
                router.visit(user.roleContexts[0].dashboardPath, { replace: true });
            } else {
                router.visit('/role-selector', { replace: true });
            }
        }
    };

    // Wait for user to be loaded before showing the full UI
    if (!user) {
        return (
            <div className="max-w-md mx-auto opacity-0 animate-[fadeIn_0.8s_ease-out_forwards]">
                <Card padding="lg" className="shadow-xl border border-gray-200 dark:border-gray-700">
                    <div className="text-center mb-6">
                        <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-50 dark:bg-blue-900/20 mb-6">
                            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        </div>
                        <h1 className="text-2xl font-semibold text-gray-900 dark:text-white mb-2">
                            Cargando...
                        </h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Verificando sesión
                        </p>
                    </div>
                </Card>
            </div>
        );
    }

    return (
        <div className="max-w-md mx-auto opacity-0 animate-[fadeIn_0.8s_ease-out_forwards]">
            <Card padding="lg" className="shadow-xl border border-gray-200 dark:border-gray-700">
                {/* Header */}
                <div className="text-center mb-6">
                    {verificationStatus === 'pending' && urlToken && (
                        <>
                            {/* Loading minimalista */}
                            <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-50 dark:bg-blue-900/20 mb-6">
                                <svg className="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="3"/>
                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                </svg>
                            </div>
                            <h1 className="text-2xl font-semibold text-gray-900 dark:text-white mb-2">
                                {t('onboarding.verify.verifying')}
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                {t('onboarding.verify.wait')}
                            </p>
                        </>
                    )}

                    {verificationStatus === 'success' && (
                        <>
                            {/* Animación draw: círculo + check minimalista */}
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
                                {t('onboarding.verify.success')}
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                {t('onboarding.verify.redirecting')}
                            </p>
                        </>
                    )}

                    {verificationStatus === 'error' && (
                        <>
                            {/* Error minimalista */}
                            <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/30 mb-6">
                                <svg className="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2.5}
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </div>
                            <h1 className="text-2xl font-semibold text-gray-900 dark:text-white mb-2">
                                {t('onboarding.verify.error')}
                            </h1>
                        </>
                    )}

                    {!urlToken && (
                        <>
                            {/* Email pendiente minimalista */}
                            <div className="relative inline-flex items-center justify-center mb-6">
                                <div className="w-16 h-16 rounded-full bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center animate-shake-continuous">
                                    <svg className="w-20 h-20 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                        />
                                    </svg>
                                </div>
                                {/* Badge contador de reenvíos */}
                                {resendCount >= 1 && (
                                    <div className="absolute -top-1 -right-1 w-6 h-6 bg-blue-600 dark:bg-blue-500 text-white text-xs font-semibold rounded-full flex items-center justify-center shadow-md animate-badge-pulse">
                                        {resendCount}
                                    </div>
                                )}
                            </div>
                            <h1 className="text-2xl font-semibold text-gray-900 dark:text-white mb-2">
                                {t('onboarding.verify.title')}
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                {t('onboarding.verify.subtitle')}
                            </p>
                        </>
                    )}
                </div>

                {/* Message Alert */}
                {message && (
                    <Alert
                        variant={verificationStatus === 'success' ? 'success' : verificationStatus === 'error' ? 'error' : 'info'}
                        className="mb-4"
                    >
                        {message}
                    </Alert>
                )}

                {/* User Info */}
                {!urlToken && (
                    <div className="mb-6 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
                        <p className="text-sm text-gray-700 dark:text-gray-300">
                            <span className="font-medium text-gray-900 dark:text-white">{t('auth.email')}:</span>
                            <br />
                            <span className="text-gray-600 dark:text-gray-400">{user.email}</span>
                        </p>
                        {user.emailVerified && (
                            <p className="text-xs text-green-600 dark:text-green-400 mt-2 flex items-center gap-1">
                                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M5 13l4 4L19 7" />
                                </svg>
                                {t('onboarding.verify.email_verified')}
                            </p>
                        )}
                    </div>
                )}

                {/* Actions */}
                <div className="space-y-3">
                    {verificationStatus === 'success' && (
                        <Button 
                            size="lg" 
                            className="w-full bg-green-600 hover:bg-green-700 text-white dark:bg-green-600 dark:hover:bg-green-700"
                            onClick={() => {
                                // Verificar si ya completó onboarding
                                const hasCompletedOnboarding = user?.onboardingCompletedAt !== null && user?.onboardingCompletedAt !== undefined;
                                
                                if (!hasCompletedOnboarding) {
                                    // Usuario nuevo, ir a onboarding
                                    router.visit('/onboarding/profile', { replace: true });
                                } else {
                                    // Usuario existente, ir a dashboard según roles
                                    if (user?.roleContexts && user.roleContexts.length === 1) {
                                        router.visit(user.roleContexts[0].dashboardPath, { replace: true });
                                    } else {
                                        router.visit('/role-selector', { replace: true });
                                    }
                                }
                            }}
                        >
                            {t('onboarding.verify.continue')}
                        </Button>
                    )}

                    {!urlToken && !user.emailVerified && (
                        <>
                            <Button
                                variant="primary"
                                size="lg"
                                className="w-full"
                                onClick={handleResend}
                                disabled={!canResend || resending || countdown > 0}
                            >
                                {resending ? (
                                    t('onboarding.verify.resending')
                                ) : countdown > 0 ? (
                                    t('onboarding.verify.resend_countdown', { seconds: countdown.toString() })
                                ) : (
                                    t('onboarding.verify.resend')
                                )}
                            </Button>

                            {/* Botón Omitir con Advertencia */}
                            {!showSkipWarning ? (
                                <Button
                                    variant="ghost"
                                    size="lg"
                                    className="w-full"
                                    onClick={() => setShowSkipWarning(true)}
                                >
                                    {t('onboarding.verify.skip')}
                                </Button>
                            ) : (
                                <div className="space-y-3 animate-[fadeIn_0.3s_ease-out]">
                                    <Alert variant="warning" className="text-left">
                                        <div className="flex items-start gap-3">
                                            <AlertTriangle className="w-5 h-5 text-yellow-600 dark:text-yellow-500 flex-shrink-0 mt-0.5" />
                                            <div>
                                                <p className="font-medium text-yellow-900 dark:text-yellow-200 mb-1">
                                                    {t('onboarding.verify.skip_warning_title')}
                                                </p>
                                                <p className="text-sm text-yellow-800 dark:text-yellow-300">
                                                    {t('onboarding.verify.skip_warning_message')}
                                                </p>
                                            </div>
                                        </div>
                                    </Alert>
                                    <div className="flex gap-3">
                                        <Button
                                            variant="ghost"
                                            size="md"
                                            className="flex-1"
                                            onClick={() => setShowSkipWarning(false)}
                                        >
                                            {t('onboarding.verify.cancel')}
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="md"
                                            className="flex-1"
                                            onClick={handleSkip}
                                        >
                                            {t('onboarding.verify.continue')}
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </>
                    )}

                    {(verificationStatus === 'error' || !urlToken) && !showSkipWarning && (
                        <Button 
                            variant="ghost" 
                            size="lg" 
                            className="w-full"
                            onClick={() => router.visit('/login', { replace: true })}
                        >
                            {t('onboarding.verify.back_to_login')}
                        </Button>
                    )}
                </div>
            </Card>
        </div>
    );
}

export default function VerifyEmail(props: VerifyEmailPageProps) {
    return (
        <OnboardingLayout title="Verificar Email">
            <VerifyEmailContent {...props} />
        </OnboardingLayout>
    );
}

