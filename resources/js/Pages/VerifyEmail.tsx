import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';

interface VerifyEmailProps {
    token: string;
    userId: string;
}

type VerificationStatus = 'loading' | 'success' | 'error' | 'already-verified';

function VerifyEmail({ token, userId }: VerifyEmailProps) {
    const [status, setStatus] = useState<VerificationStatus>('loading');
    const [message, setMessage] = useState('');

    useEffect(() => {
        verifyEmail();
    }, []);

    const verifyEmail = async () => {
        try {
            const response = await fetch('/graphql', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    query: `
                        mutation VerifyEmail($token: String!) {
                            verifyEmail(token: $token) {
                                success
                                message
                                canResend
                                resendAvailableAt
                            }
                        }
                    `,
                    variables: {
                        token: token,
                    },
                }),
            });

            const result = await response.json();

            if (result.errors) {
                setStatus('error');
                setMessage(result.errors[0]?.message || 'Error al verificar el email');
            } else if (result.data?.verifyEmail?.success) {
                setStatus('success');
                setMessage(result.data.verifyEmail.message || '¡Email verificado correctamente!');
            } else {
                setStatus('error');
                setMessage(result.data?.verifyEmail?.message || 'No se pudo verificar el email');
            }
        } catch (error) {
            setStatus('error');
            setMessage('Error de conexión. Por favor, intenta nuevamente.');
        }
    };

    return (
        <>
            <Head title="Verificar Email" />
            <div className="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 flex items-center justify-center p-4">
                <div className="bg-white p-8 rounded-2xl shadow-2xl max-w-md w-full">
                    {/* Loading State */}
                    {status === 'loading' && (
                        <div className="text-center">
                            <div className="mb-6 flex justify-center">
                                <div className="relative">
                                    <div className="w-20 h-20 border-4 border-indigo-200 rounded-full"></div>
                                    <div className="w-20 h-20 border-4 border-indigo-600 rounded-full absolute top-0 left-0 animate-spin border-t-transparent"></div>
                                </div>
                            </div>
                            <h1 className="text-2xl font-bold text-gray-800 mb-2">
                                Verificando tu email...
                            </h1>
                            <p className="text-gray-600">
                                Por favor espera un momento
                            </p>
                        </div>
                    )}

                    {/* Success State */}
                    {status === 'success' && (
                        <div className="text-center">
                            <div className="mb-6 flex justify-center">
                                <div className="relative">
                                    <svg
                                        className="w-20 h-20 text-green-500 animate-check-scale"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle
                                            className="animate-check-circle"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            strokeWidth="2"
                                            fill="none"
                                        />
                                        <path
                                            className="animate-check-path"
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth="2"
                                            d="M9 12l2 2 4-4"
                                        />
                                    </svg>
                                </div>
                            </div>
                            <h1 className="text-2xl font-bold text-gray-800 mb-2">
                                ¡Email Verificado!
                            </h1>
                            <p className="text-gray-600 mb-6">
                                {message}
                            </p>
                            <a
                                href="/"
                                className="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition-colors"
                            >
                                Ir al inicio
                            </a>
                        </div>
                    )}

                    {/* Error State */}
                    {status === 'error' && (
                        <div className="text-center">
                            <div className="mb-6 flex justify-center">
                                <svg
                                    className="w-20 h-20 text-red-500 animate-shake"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="2" fill="none" />
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 9l-6 6M9 9l6 6" />
                                </svg>
                            </div>
                            <h1 className="text-2xl font-bold text-gray-800 mb-2">
                                Error de Verificación
                            </h1>
                            <p className="text-gray-600 mb-6">
                                {message}
                            </p>
                            <button
                                onClick={verifyEmail}
                                className="inline-block bg-red-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-700 transition-colors mr-2"
                            >
                                Intentar nuevamente
                            </button>
                            <a
                                href="/"
                                className="inline-block bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition-colors"
                            >
                                Volver al inicio
                            </a>
                        </div>
                    )}
                </div>

                {/* Custom animations styles */}
                <style>{`
                    @keyframes check-scale {
                        0% { transform: scale(0); }
                        50% { transform: scale(1.1); }
                        100% { transform: scale(1); }
                    }

                    @keyframes check-circle {
                        0% { stroke-dasharray: 0 100; }
                        100% { stroke-dasharray: 100 100; }
                    }

                    @keyframes check-path {
                        0% { stroke-dasharray: 0 20; }
                        100% { stroke-dasharray: 20 20; }
                    }

                    @keyframes shake {
                        0%, 100% { transform: translateX(0); }
                        10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
                        20%, 40%, 60%, 80% { transform: translateX(10px); }
                    }

                    .animate-check-scale {
                        animation: check-scale 0.5s ease-in-out;
                    }

                    .animate-check-circle {
                        animation: check-circle 0.6s ease-in-out 0.2s forwards;
                        stroke-dasharray: 0 100;
                    }

                    .animate-check-path {
                        animation: check-path 0.4s ease-in-out 0.6s forwards;
                        stroke-dasharray: 0 20;
                    }

                    .animate-shake {
                        animation: shake 0.5s ease-in-out;
                    }
                `}</style>
            </div>
        </>
    );
}

export default VerifyEmail;
