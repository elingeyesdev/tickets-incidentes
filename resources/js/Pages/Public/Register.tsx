/**
 * Register Page - Mejorado con UX Professional
 * Ahora usa el hook useRegister del feature de authentication
 */

import { Link } from '@inertiajs/react';
import { PublicLayout } from '@/Layouts/Public/PublicLayout';
import { Card, Button, Input, Alert, GoogleLogo } from '@/Components/ui';
import { useLocale } from '@/contexts';
import { useRegister } from '@/Features/authentication';
import { Eye, EyeOff, Mail, Lock, User, AlertCircle, CheckCircle2 } from 'lucide-react';

function RegisterContent() {
    const { t } = useLocale();
    const {
        formData,
        setFormData,
        showPassword,
        setShowPassword,
        showPasswordConfirmation,
        setShowPasswordConfirmation,
        touched,
        setTouched,
        validation,
        loading,
        error,
        isFormValid,
        handleSubmit,
        handleGoogleRegister,
    } = useRegister();

    // Password strength indicator component
    const PasswordStrengthIndicator = ({ strength }: { strength: number }) => {
        const getStrengthColor = () => {
            if (strength >= 4) return 'bg-green-500';
            if (strength >= 3) return 'bg-yellow-500';
            return 'bg-red-500';
        };

        const getStrengthText = () => {
            if (strength >= 4) return t('auth.register.password_strong');
            if (strength >= 3) return t('auth.register.password_medium');
            return t('auth.register.password_weak');
        };

        return (
            <div className="mt-2">
                <div className="flex gap-1 mb-1">
                    {[1, 2, 3, 4, 5].map((level) => (
                        <div
                            key={level}
                            className={`h-1 flex-1 rounded ${
                                level <= strength ? getStrengthColor() : 'bg-gray-200 dark:bg-gray-700'
                            }`}
                        />
                    ))}
                </div>
                {formData.password && (
                    <p className="text-xs text-gray-500 dark:text-gray-400">{getStrengthText()}</p>
                )}
            </div>
        );
    };

    return (
        <div className="max-w-lg mx-auto mt-12 transition-all duration-300 animate-fadeIn">
            <Card padding="lg" className="shadow-xl">
                {/* Header */}
                <div className="text-center mb-6">
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                        {t('auth.register.title')}
                    </h1>
                    <p className="text-gray-600 dark:text-gray-400 mt-2">
                        {t('auth.register.subtitle')}
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
                    {/* First Name and Last Name */}
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Input
                                label={t('auth.register.first_name')}
                                type="text"
                                value={formData.firstName}
                                onChange={(e) => setFormData({ ...formData, firstName: e.target.value })}
                                onBlur={() => setTouched({ ...touched, firstName: true })}
                                required
                                placeholder={t('auth.register.first_name_placeholder')}
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
                        </div>

                        <div>
                            <Input
                                label={t('auth.register.last_name')}
                                type="text"
                                value={formData.lastName}
                                onChange={(e) => setFormData({ ...formData, lastName: e.target.value })}
                                onBlur={() => setTouched({ ...touched, lastName: true })}
                                required
                                placeholder={t('auth.register.last_name_placeholder')}
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
                    </div>

                    {/* Email */}
                    <div>
                        <Input
                            label={t('auth.register.email')}
                            type="email"
                            value={formData.email}
                            onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                            onBlur={() => setTouched({ ...touched, email: true })}
                            required
                            placeholder={t('auth.register.email_placeholder')}
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
                            label={t('auth.register.password')}
                            type={showPassword ? 'text' : 'password'}
                            value={formData.password}
                            onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                            onBlur={() => setTouched({ ...touched, password: true })}
                            required
                            placeholder={t('auth.register.password_placeholder')}
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
                        />
                        <PasswordStrengthIndicator strength={validation.password.strength} />
                    </div>

                    {/* Password Confirmation */}
                    <div>
                        <Input
                            label={t('auth.register.password_confirmation')}
                            type={showPasswordConfirmation ? 'text' : 'password'}
                            value={formData.passwordConfirmation}
                            onChange={(e) =>
                                setFormData({ ...formData, passwordConfirmation: e.target.value })
                            }
                            onBlur={() => setTouched({ ...touched, passwordConfirmation: true })}
                            required
                            placeholder={t('auth.register.password_confirmation_placeholder')}
                            leftIcon={<Lock className="h-5 w-5" />}
                            rightIcon={
                                <button
                                    type="button"
                                    onClick={() => setShowPasswordConfirmation(!showPasswordConfirmation)}
                                    className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none"
                                >
                                    {showPasswordConfirmation ? (
                                        <EyeOff className="h-5 w-5" />
                                    ) : (
                                        <Eye className="h-5 w-5" />
                                    )}
                                </button>
                            }
                            error={
                                touched.passwordConfirmation && !validation.passwordConfirmation.valid
                                    ? validation.passwordConfirmation.message
                                    : undefined
                            }
                            helperText={
                                touched.passwordConfirmation && validation.passwordConfirmation.valid
                                    ? validation.passwordConfirmation.message
                                    : undefined
                            }
                        />
                    </div>

                    {/* Terms and Privacy */}
                    <div className="space-y-2">
                        <label className="flex items-start cursor-pointer">
                            <input
                                type="checkbox"
                                checked={formData.acceptsTerms}
                                onChange={(e) =>
                                    setFormData({ ...formData, acceptsTerms: e.target.checked })
                                }
                                className="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <span className="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                {t('auth.register.accept_terms')}{' '}
                                <Link
                                    href="/terms"
                                    className="text-blue-600 hover:text-blue-700 dark:text-blue-400"
                                >
                                    {t('auth.register.terms_link')}
                                </Link>
                            </span>
                        </label>

                        <label className="flex items-start cursor-pointer">
                            <input
                                type="checkbox"
                                checked={formData.acceptsPrivacyPolicy}
                                onChange={(e) =>
                                    setFormData({ ...formData, acceptsPrivacyPolicy: e.target.checked })
                                }
                                className="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <span className="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                {t('auth.register.accept_privacy')}{' '}
                                <Link
                                    href="/privacy"
                                    className="text-blue-600 hover:text-blue-700 dark:text-blue-400"
                                >
                                    {t('auth.register.privacy_link')}
                                </Link>
                            </span>
                        </label>
                    </div>

                    {/* Submit Button */}
                    <Button
                        type="submit"
                        fullWidth
                        isLoading={loading}
                        disabled={!isFormValid || loading}
                    >
                        {loading ? t('auth.register.submitting') : t('auth.register.submit')}
                    </Button>
                </form>

                {/* Divider */}
                <div className="relative my-6">
                    <div className="absolute inset-0 flex items-center">
                        <div className="w-full border-t border-gray-300 dark:border-gray-600"></div>
                    </div>
                    <div className="relative flex justify-center text-sm">
                        <span className="px-2 bg-white dark:bg-gray-800 text-gray-500">
                            {t('auth.register.or')}
                        </span>
                    </div>
                </div>

                {/* Google Sign Up */}
                <Button
                    type="button"
                    variant="outline"
                    fullWidth
                    onClick={handleGoogleRegister}
                    className="mb-4"
                >
                    <GoogleLogo className="w-5 h-5 mr-2" />
                    {t('auth.register.google')}
                </Button>

                {/* Login Link */}
                <div className="text-center">
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                        {t('auth.register.have_account')}{' '}
                        <Link
                            href="/login"
                            className="text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium"
                        >
                            {t('auth.register.login_link')}
                        </Link>
                    </p>
                </div>
            </Card>
        </div>
    );
}

export default function Register() {
    return (
        <PublicLayout title="Registrarse">
            <RegisterContent />
        </PublicLayout>
    );
}
