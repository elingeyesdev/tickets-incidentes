/**
 * Login Page - Mejorado con UX Professional
 * Ahora usa el hook useLogin del feature de authentication
 */

import { Link } from '@inertiajs/react';
import { PublicLayout } from '@/Layouts/Public/PublicLayout';
import { Card, Button, Input, Alert, GoogleLogo } from '@/Components/ui';
import { useLocale } from '@/contexts';
import { useLogin } from '@/Features/authentication';
import { Eye, EyeOff, Mail, Lock, AlertCircle, CheckCircle2 } from 'lucide-react';

function LoginContent() {
    const { t } = useLocale();
    const {
        formData,
        setFormData,
        showPassword,
        setShowPassword,
        touched,
        setTouched,
        validation,
        loading,
        error,
        isFormValid,
        handleSubmit,
        handleGoogleLogin,
    } = useLogin();

    return (
        <div className="max-w-md mx-auto mt-12 transition-all duration-300 animate-fadeIn">
            <Card padding="lg" className="shadow-xl">
                {/* Header */}
                <div className="text-center mb-6">
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                        {t('auth.login.title')}
                    </h1>
                    <p className="text-gray-600 dark:text-gray-400 mt-2">
                        {t('auth.login.subtitle')}
                    </p>
                </div>

                {/* Error Alert */}
                {error && (
                    <Alert variant="error" className="mb-4">
                        {error.message}
                    </Alert>
                )}

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* Email */}
                    <div>
                        <Input
                            label={t('auth.login.email')}
                            type="email"
                            value={formData.email}
                            onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                            onBlur={() => setTouched({ ...touched, email: true })}
                            required
                            placeholder={t('auth.login.email_placeholder')}
                            leftIcon={<Mail className="h-5 w-5" />}
                            rightIcon={
                                touched.email && formData.email ? (
                                    validation.email.valid ? (
                                        <CheckCircle2 className="h-5 w-5 text-green-500" />
                                    ) : (
                                        <AlertCircle className="h-5 w-5 text-red-500" />
                                    )
                                ) : null
                            }
                            error={
                                touched.email && !validation.email.valid
                                    ? validation.email.message
                                    : undefined
                            }
                            helperText={
                                touched.email && validation.email.valid
                                    ? validation.email.message
                                    : undefined
                            }
                        />
                    </div>

                    {/* Password */}
                    <div>
                        <Input
                            label={t('auth.login.password')}
                            type={showPassword ? 'text' : 'password'}
                            value={formData.password}
                            onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                            onBlur={() => setTouched({ ...touched, password: true })}
                            required
                            placeholder={t('auth.login.password_placeholder')}
                            leftIcon={<Lock className="h-5 w-5" />}
                            rightIcon={
                                <button
                                    type="button"
                                    onClick={() => setShowPassword(!showPassword)}
                                    className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none"
                                >
                                    {showPassword ? (
                                        <EyeOff className="h-5 w-5" />
                                    ) : (
                                        <Eye className="h-5 w-5" />
                                    )}
                                </button>
                            }
                            error={
                                touched.password && !validation.password.valid
                                    ? validation.password.message
                                    : undefined
                            }
                            helperText={
                                touched.password && validation.password.valid
                                    ? validation.password.message
                                    : undefined
                            }
                        />
                    </div>

                    {/* Remember Me & Forgot Password */}
                    <div className="flex items-center justify-between">
                        <label className="flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                checked={formData.rememberMe}
                                onChange={(e) =>
                                    setFormData({ ...formData, rememberMe: e.target.checked })
                                }
                                className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <span className="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                {t('auth.login.remember_me')}
                            </span>
                        </label>

                        <Link
                            href="/forgot-password"
                            className="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400"
                        >
                            {t('auth.login.forgot_password')}
                        </Link>
                    </div>

                    {/* Submit Button */}
                    <Button
                        type="submit"
                        fullWidth
                        isLoading={loading}
                        disabled={!isFormValid || loading}
                    >
                        {loading ? t('auth.login.submitting') : t('auth.login.submit')}
                    </Button>
                </form>

                {/* Divider */}
                <div className="relative my-6">
                    <div className="absolute inset-0 flex items-center">
                        <div className="w-full border-t border-gray-300 dark:border-gray-600"></div>
                    </div>
                    <div className="relative flex justify-center text-sm">
                        <span className="px-2 bg-white dark:bg-gray-800 text-gray-500">
                            {t('auth.login.or')}
                        </span>
                    </div>
                </div>

                {/* Google Sign In */}
                <Button
                    type="button"
                    variant="outline"
                    fullWidth
                    onClick={handleGoogleLogin}
                    className="mb-4"
                >
                    <GoogleLogo className="w-5 h-5 mr-2" />
                    {t('auth.login.google')}
                </Button>

                {/* Register Link */}
                <div className="text-center">
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                        {t('auth.login.no_account')}{' '}
                        <Link
                            href="/register-user"
                            className="text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium"
                        >
                            {t('auth.login.register_link')}
                        </Link>
                    </p>
                </div>
            </Card>
        </div>
    );
}

export default function Login() {
    return (
        <PublicLayout title="Iniciar Sesión">
            <LoginContent />
        </PublicLayout>
    );
}
