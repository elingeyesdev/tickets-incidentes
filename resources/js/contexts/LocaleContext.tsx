/**
 * LocaleContext - Gestión Global de Idioma
 * 
 * Responsabilidades:
 * - Idioma actual (es/en)
 * - Traducciones
 * - Sincronización con localStorage
 * - Sincronización con preferencias del usuario en BD
 */

import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { useAuth } from './AuthContext';

type Locale = 'es' | 'en';

interface Translations {
    [key: string]: string;
}

interface LocaleContextType {
    locale: Locale;
    setLocale: (locale: Locale) => void;
    t: (key: string, params?: Record<string, string>) => string;
    syncWithUserPreferences: () => void;
}

const LocaleContext = createContext<LocaleContextType | undefined>(undefined);

const LOCALE_STORAGE_KEY = 'helpdesk_locale';

// Traducciones completas
const translations: Record<Locale, Translations> = {
    es: {
        // App
        'app.name': 'Helpdesk',
        
        // Common
        'common.welcome': 'Bienvenido',
        'common.logout': 'Cerrar Sesión',
        'common.login': 'Iniciar Sesión',
        'common.register': 'Registrarse',
        'common.save': 'Guardar',
        'common.cancel': 'Cancelar',
        'common.delete': 'Eliminar',
        'common.edit': 'Editar',
        'common.search': 'Buscar',
        'common.loading': 'Cargando...',
        'common.error': 'Error',
        'common.success': 'Éxito',
        'common.theme': 'Tema',
        
        // Auth - Common
        'auth.logout': 'Cerrar Sesión',
        
        // Auth - Login
        'auth.login.title': 'Iniciar Sesión',
        'auth.login.subtitle': 'Accede a tu cuenta',
        'auth.login.email': 'Correo Electrónico',
        'auth.login.email_placeholder': 'tu@email.com',
        'auth.login.password': 'Contraseña',
        'auth.login.password_placeholder': 'Tu contraseña',
        'auth.login.remember_me': 'Recordarme',
        'auth.login.forgot_password': '¿Olvidaste tu contraseña?',
        'auth.login.submit': 'Iniciar Sesión',
        'auth.login.submitting': 'Iniciando sesión...',
        'auth.login.no_account': '¿No tienes una cuenta?',
        'auth.login.register_link': 'Regístrate aquí',
        'auth.login.or': 'o',
        'auth.login.google': 'Continuar con Google',
        
        // Auth - Register
        'auth.register.title': 'Crear Cuenta',
        'auth.register.subtitle': 'Regístrate para comenzar',
        'auth.register.email': 'Correo Electrónico',
        'auth.register.email_placeholder': 'tu@email.com',
        'auth.register.password': 'Contraseña',
        'auth.register.password_placeholder': 'Mínimo 8 caracteres',
        'auth.register.password_confirmation': 'Confirmar Contraseña',
        'auth.register.password_confirmation_placeholder': 'Repite tu contraseña',
        'auth.register.first_name': 'Nombre',
        'auth.register.first_name_placeholder': 'Tu nombre',
        'auth.register.last_name': 'Apellido',
        'auth.register.last_name_placeholder': 'Tu apellido',
        'auth.register.accept_terms': 'Acepto los',
        'auth.register.accept_privacy': 'Acepto la',
        'auth.register.terms_link': 'Términos de Servicio',
        'auth.register.privacy_link': 'Política de Privacidad',
        'auth.register.password_weak': 'Contraseña débil',
        'auth.register.password_medium': 'Contraseña media',
        'auth.register.password_strong': 'Contraseña fuerte',
        'auth.register.submit': 'Registrarse',
        'auth.register.submitting': 'Creando cuenta...',
        'auth.register.have_account': '¿Ya tienes una cuenta?',
        'auth.register.login_link': 'Inicia sesión aquí',
        'auth.register.or': 'o',
        'auth.register.google': 'Registrarse con Google',
        
        // Auth - General
        'auth.email': 'Correo Electrónico',
        'auth.password': 'Contraseña',
        'auth.remember_me': 'Recordarme',
        'auth.forgot_password': '¿Olvidaste tu contraseña?',
        
        // Navigation
        'nav.dashboard': 'Panel',
        'nav.tickets': 'Tickets',
        'nav.profile': 'Perfil',
        'nav.settings': 'Configuración',
        
        // Theme
        'theme.light': 'Claro',
        'theme.dark': 'Oscuro',
        'theme.system': 'Sistema',
        
        // Welcome Page
        'welcome.badge': 'Sistema de Gestión de Incidentes',
        'welcome.hero.title': 'Gestiona el soporte de tu empresa de manera',
        'welcome.hero.title_highlight': 'profesional',
        'welcome.hero.subtitle': 'Plataforma completa de helpdesk que permite a las empresas gestionar tickets de soporte, clasificar incidentes y brindar atención al cliente de forma eficiente.',
        'welcome.hero.btn_company': 'Registrar Empresa',
        'welcome.hero.btn_login': 'Iniciar Sesión',
        'welcome.hero.btn_user': 'Registrar Usuario',
        
        'welcome.features.title': 'Todo lo que necesitas para gestionar soporte',
        'welcome.features.subtitle': 'Herramientas profesionales diseñadas para empresas que buscan excelencia en atención al cliente',
        
        'welcome.features.security.title': 'Gestión Segura',
        'welcome.features.security.description': 'Sistema seguro para múltiples empresas con datos protegidos y acceso controlado',
        
        'welcome.features.speed.title': 'Respuesta Rápida',
        'welcome.features.speed.description': 'Clasificación automática por categorías y prioridades para resolver incidentes eficientemente',
        
        'welcome.features.multi.title': 'Multi-empresa',
        'welcome.features.multi.description': 'Diseñado para ofrecer servicios de helpdesk a múltiples empresas desde una sola plataforma',
        
        'welcome.benefits.title': 'Optimiza la atención al cliente de tu empresa',
        'welcome.benefits.tickets.title': 'Tickets Organizados',
        'welcome.benefits.tickets.description': 'Clasifica y prioriza todos los incidentes automáticamente',
        'welcome.benefits.tracking.title': 'Seguimiento Completo',
        'welcome.benefits.tracking.description': 'Historial detallado de todos los tickets y resoluciones',
        'welcome.benefits.scalability.title': 'Escalabilidad',
        'welcome.benefits.scalability.description': 'Crece con tu empresa, desde startups hasta grandes corporaciones',
        
        'welcome.cta.title': '¿Listo para comenzar?',
        'welcome.cta.subtitle': 'Registra tu empresa o inicia sesión para gestionar tickets profesionalmente',
        'welcome.cta.btn_register': 'Registrar Mi Empresa',
        'welcome.cta.btn_login': 'Ya tengo cuenta - Iniciar Sesión',
        
        'welcome.footer.copyright': 'HELPDESK. Sistema profesional de gestión de incidentes para empresas.',
        
        // Language
        'language.spanish': 'Español',
        'language.english': 'Inglés',
        'language.change': 'Cambiar idioma',
        
        // Breadcrumbs
        'breadcrumb.login': 'Iniciar Sesión',
        'breadcrumb.register': 'Registrarse',
        'breadcrumb.register_company': 'Registrar Empresa',
        'breadcrumb.verify_email': 'Verificar Cuenta',
        
        // Role Selector
        'auth.role_selector.title': '¡Bienvenido de vuelta!',
        'auth.role_selector.subtitle': 'Selecciona el rol con el que deseas trabajar hoy',
        'auth.role_selector.no_roles_title': 'Sin Roles Asignados',
        'auth.role_selector.no_roles_message': 'Tu cuenta no tiene roles asignados actualmente. Contacta al administrador.',
        
        // ComingSoon Page
        'comingsoon.title': '¡Próximamente!',
        'comingsoon.subtitle': 'Esta función está en desarrollo',
        'comingsoon.description': 'Estamos trabajando arduamente para traerte esta funcionalidad. Pronto estará disponible con todas las características que necesitas.',
        'comingsoon.btn_home': 'Volver al Inicio',
        'comingsoon.btn_back': 'Regresar',
        'comingsoon.features_title': 'Próximamente disponible',
        'comingsoon.feature1': 'Dashboard completo',
        'comingsoon.feature2': 'Gestión de tickets',
        'comingsoon.feature3': 'Reportes avanzados',
        
        // Onboarding - Verify Email
        'onboarding.verify.title': 'Verifica tu email',
        'onboarding.verify.subtitle': 'Hemos enviado un enlace de verificación a tu correo',
        'onboarding.verify.verifying': 'Verificando email...',
        'onboarding.verify.wait': 'Un momento por favor',
        'onboarding.verify.success': 'Email verificado',
        'onboarding.verify.redirecting': 'Redirigiendo...',
        'onboarding.verify.error': 'Error de verificación',
        'onboarding.verify.resend': 'Reenviar email de verificación',
        'onboarding.verify.resending': 'Enviando...',
        'onboarding.verify.resend_countdown': 'Reenviar en {seconds}s',
        'onboarding.verify.resend_success': 'Email de verificación enviado',
        'onboarding.verify.resend_error': 'Error al reenviar email',
        'onboarding.verify.skip': 'Omitir por ahora',
        'onboarding.verify.skip_warning_title': 'Cuenta sin verificar',
        'onboarding.verify.skip_warning_message': 'Solo podrás enviar máximo 2 tickets. Verifica tu email para acceso completo.',
        'onboarding.verify.continue': 'Continuar',
        'onboarding.verify.cancel': 'Cancelar',
        'onboarding.verify.back_to_login': 'Volver al Login',
        'onboarding.verify.email_verified': 'Email verificado',
        
        // Onboarding - Complete Profile
        'onboarding.profile.title': 'Completar tu Perfil',
        'onboarding.profile.subtitle': 'Necesitamos algunos datos básicos para configurar tu cuenta.',
        'onboarding.profile.step': 'Paso 1 de 2',
        'onboarding.profile.step_label': 'Información Personal',
        'onboarding.profile.first_name': 'Nombre',
        'onboarding.profile.first_name_placeholder': 'Tu nombre',
        'onboarding.profile.last_name': 'Apellido',
        'onboarding.profile.last_name_placeholder': 'Tu apellido',
        'onboarding.profile.phone': 'Número de Teléfono (Opcional)',
        'onboarding.profile.phone_placeholder': 'Ej: 70012345',
        'onboarding.profile.continue': 'Continuar',
        'onboarding.profile.skip': 'Omitir por ahora',
        'onboarding.profile.hint': 'Puedes actualizar tu foto de perfil más tarde',
        'onboarding.profile.saving': 'Guardando...',
        
        // Onboarding - Configure Preferences
        'onboarding.preferences.title': 'Configura tus Preferencias',
        'onboarding.preferences.subtitle': 'Personaliza cómo quieres usar la plataforma',
        'onboarding.preferences.step': 'Paso 2 de 2',
        'onboarding.preferences.step_label': 'Preferencias',
        'onboarding.preferences.theme': 'Tema de la Aplicación',
        'onboarding.preferences.theme_light': 'Claro',
        'onboarding.preferences.theme_dark': 'Oscuro',
        'onboarding.preferences.language': 'Idioma',
        'onboarding.preferences.language_es': 'Español',
        'onboarding.preferences.language_en': 'English',
        'onboarding.preferences.timezone': 'Zona Horaria',
        'onboarding.preferences.notifications': 'Notificaciones',
        'onboarding.preferences.web_push': 'Recibir notificaciones web push',
        'onboarding.preferences.ticket_notifications': 'Notificaciones por tickets asignados',
        'onboarding.preferences.continue': 'Finalizar',
        'onboarding.preferences.skip': 'Omitir por ahora',
        'onboarding.preferences.hint': 'Puedes cambiar estas preferencias en cualquier momento',
        'onboarding.preferences.finalizing': 'Finalizando...',
        'onboarding.preferences.success': '¡Todo listo!',
    },
    en: {
        // App
        'app.name': 'Helpdesk',
        
        // Common
        'common.welcome': 'Welcome',
        'common.logout': 'Logout',
        'common.login': 'Login',
        'common.register': 'Register',
        'common.save': 'Save',
        'common.cancel': 'Cancel',
        'common.delete': 'Delete',
        'common.edit': 'Edit',
        'common.search': 'Search',
        'common.loading': 'Loading...',
        'common.error': 'Error',
        'common.success': 'Success',
        'common.theme': 'Theme',
        
        // Auth - Common
        'auth.logout': 'Logout',
        
        // Auth - Login
        'auth.login.title': 'Sign In',
        'auth.login.subtitle': 'Access your account',
        'auth.login.email': 'Email',
        'auth.login.email_placeholder': 'your@email.com',
        'auth.login.password': 'Password',
        'auth.login.password_placeholder': 'Your password',
        'auth.login.remember_me': 'Remember me',
        'auth.login.forgot_password': 'Forgot your password?',
        'auth.login.submit': 'Sign In',
        'auth.login.submitting': 'Signing in...',
        'auth.login.no_account': "Don't have an account?",
        'auth.login.register_link': 'Register here',
        'auth.login.or': 'or',
        'auth.login.google': 'Continue with Google',
        
        // Auth - Register
        'auth.register.title': 'Create Account',
        'auth.register.subtitle': 'Sign up to get started',
        'auth.register.email': 'Email',
        'auth.register.email_placeholder': 'your@email.com',
        'auth.register.password': 'Password',
        'auth.register.password_placeholder': 'Minimum 8 characters',
        'auth.register.password_confirmation': 'Confirm Password',
        'auth.register.password_confirmation_placeholder': 'Repeat your password',
        'auth.register.first_name': 'First Name',
        'auth.register.first_name_placeholder': 'Your first name',
        'auth.register.last_name': 'Last Name',
        'auth.register.last_name_placeholder': 'Your last name',
        'auth.register.accept_terms': 'I accept the',
        'auth.register.accept_privacy': 'I accept the',
        'auth.register.terms_link': 'Terms of Service',
        'auth.register.privacy_link': 'Privacy Policy',
        'auth.register.password_weak': 'Weak password',
        'auth.register.password_medium': 'Medium password',
        'auth.register.password_strong': 'Strong password',
        'auth.register.submit': 'Sign Up',
        'auth.register.submitting': 'Creating account...',
        'auth.register.have_account': 'Already have an account?',
        'auth.register.login_link': 'Sign in here',
        'auth.register.or': 'or',
        'auth.register.google': 'Sign up with Google',
        
        // Auth - General
        'auth.email': 'Email',
        'auth.password': 'Password',
        'auth.remember_me': 'Remember me',
        'auth.forgot_password': 'Forgot your password?',
        
        // Navigation
        'nav.dashboard': 'Dashboard',
        'nav.tickets': 'Tickets',
        'nav.profile': 'Profile',
        'nav.settings': 'Settings',
        
        // Theme
        'theme.light': 'Light',
        'theme.dark': 'Dark',
        'theme.system': 'System',
        
        // Welcome Page
        'welcome.badge': 'Incident Management System',
        'welcome.hero.title': 'Manage your company support in a',
        'welcome.hero.title_highlight': 'professional',
        'welcome.hero.subtitle': 'Complete helpdesk platform that allows companies to manage support tickets, classify incidents and provide customer service efficiently.',
        'welcome.hero.btn_company': 'Register Company',
        'welcome.hero.btn_login': 'Sign In',
        'welcome.hero.btn_user': 'Register User',
        
        'welcome.features.title': 'Everything you need to manage support',
        'welcome.features.subtitle': 'Professional tools designed for companies seeking excellence in customer service',
        
        'welcome.features.security.title': 'Secure Management',
        'welcome.features.security.description': 'Secure system for multiple companies with protected data and controlled access',
        
        'welcome.features.speed.title': 'Fast Response',
        'welcome.features.speed.description': 'Automatic classification by categories and priorities to resolve incidents efficiently',
        
        'welcome.features.multi.title': 'Multi-company',
        'welcome.features.multi.description': 'Designed to offer helpdesk services to multiple companies from a single platform',
        
        'welcome.benefits.title': 'Optimize your company customer service',
        'welcome.benefits.tickets.title': 'Organized Tickets',
        'welcome.benefits.tickets.description': 'Classify and prioritize all incidents automatically',
        'welcome.benefits.tracking.title': 'Complete Tracking',
        'welcome.benefits.tracking.description': 'Detailed history of all tickets and resolutions',
        'welcome.benefits.scalability.title': 'Scalability',
        'welcome.benefits.scalability.description': 'Grow with your company, from startups to large corporations',
        
        'welcome.cta.title': 'Ready to get started?',
        'welcome.cta.subtitle': 'Register your company or sign in to manage tickets professionally',
        'welcome.cta.btn_register': 'Register My Company',
        'welcome.cta.btn_login': 'I already have an account - Sign In',
        
        'welcome.footer.copyright': 'HELPDESK. Professional incident management system for companies.',
        
        // Language
        'language.spanish': 'Spanish',
        'language.english': 'English',
        'language.change': 'Change language',
        
        // Breadcrumbs
        'breadcrumb.login': 'Sign In',
        'breadcrumb.register': 'Sign Up',
        'breadcrumb.register_company': 'Register Company',
        'breadcrumb.verify_email': 'Verify Account',
        
        // Role Selector
        'auth.role_selector.title': 'Welcome back!',
        'auth.role_selector.subtitle': 'Select the role you want to work with today',
        'auth.role_selector.no_roles_title': 'No Roles Assigned',
        'auth.role_selector.no_roles_message': 'Your account has no roles assigned currently. Contact the administrator.',
        
        // ComingSoon Page
        'comingsoon.title': 'Coming Soon!',
        'comingsoon.subtitle': 'This feature is under development',
        'comingsoon.description': 'We are working hard to bring you this functionality. It will be available soon with all the features you need.',
        'comingsoon.btn_home': 'Back to Home',
        'comingsoon.btn_back': 'Go Back',
        'comingsoon.features_title': 'Coming soon',
        'comingsoon.feature1': 'Complete dashboard',
        'comingsoon.feature2': 'Ticket management',
        'comingsoon.feature3': 'Advanced reports',
        
        // Onboarding - Verify Email
        'onboarding.verify.title': 'Verify your email',
        'onboarding.verify.subtitle': "We've sent a verification link to your email",
        'onboarding.verify.verifying': 'Verifying email...',
        'onboarding.verify.wait': 'One moment please',
        'onboarding.verify.success': 'Email verified',
        'onboarding.verify.redirecting': 'Redirecting...',
        'onboarding.verify.error': 'Verification error',
        'onboarding.verify.resend': 'Resend verification email',
        'onboarding.verify.resending': 'Sending...',
        'onboarding.verify.resend_countdown': 'Resend in {seconds}s',
        'onboarding.verify.resend_success': 'Verification email sent',
        'onboarding.verify.resend_error': 'Error resending email',
        'onboarding.verify.skip': 'Skip for now',
        'onboarding.verify.skip_warning_title': 'Unverified account',
        'onboarding.verify.skip_warning_message': "You'll only be able to submit a maximum of 2 tickets. Verify your email for full access.",
        'onboarding.verify.continue': 'Continue',
        'onboarding.verify.cancel': 'Cancel',
        'onboarding.verify.back_to_login': 'Back to Login',
        'onboarding.verify.email_verified': 'Email verified',
        
        // Onboarding - Complete Profile
        'onboarding.profile.title': 'Complete your Profile',
        'onboarding.profile.subtitle': 'We need some basic information to set up your account.',
        'onboarding.profile.step': 'Step 1 of 2',
        'onboarding.profile.step_label': 'Personal Information',
        'onboarding.profile.first_name': 'First Name',
        'onboarding.profile.first_name_placeholder': 'Your first name',
        'onboarding.profile.last_name': 'Last Name',
        'onboarding.profile.last_name_placeholder': 'Your last name',
        'onboarding.profile.phone': 'Phone Number (Optional)',
        'onboarding.profile.phone_placeholder': 'E.g: 70012345',
        'onboarding.profile.continue': 'Continue',
        'onboarding.profile.skip': 'Skip for now',
        'onboarding.profile.hint': 'You can update your profile picture later',
        'onboarding.profile.saving': 'Saving...',
        
        // Onboarding - Configure Preferences
        'onboarding.preferences.title': 'Configure your Preferences',
        'onboarding.preferences.subtitle': 'Customize how you want to use the platform',
        'onboarding.preferences.step': 'Step 2 of 2',
        'onboarding.preferences.step_label': 'Preferences',
        'onboarding.preferences.theme': 'App Theme',
        'onboarding.preferences.theme_light': 'Light',
        'onboarding.preferences.theme_dark': 'Dark',
        'onboarding.preferences.language': 'Language',
        'onboarding.preferences.language_es': 'Español',
        'onboarding.preferences.language_en': 'English',
        'onboarding.preferences.timezone': 'Timezone',
        'onboarding.preferences.notifications': 'Notifications',
        'onboarding.preferences.web_push': 'Receive web push notifications',
        'onboarding.preferences.ticket_notifications': 'Notifications for assigned tickets',
        'onboarding.preferences.continue': 'Finish',
        'onboarding.preferences.skip': 'Skip for now',
        'onboarding.preferences.hint': 'You can change these preferences anytime',
        'onboarding.preferences.finalizing': 'Finishing...',
        'onboarding.preferences.success': 'All set!',
    },
};

interface LocaleProviderProps {
    children: ReactNode;
}

export const LocaleProvider: React.FC<LocaleProviderProps> = ({ children }) => {
    const { user } = useAuth();

    /**
     * Inicializar idioma con esta prioridad:
     * 1. Preferencia del usuario en BD (si está autenticado)
     * 2. localStorage
     * 3. Default: 'es'
     */
    const getInitialLocale = (): Locale => {
        // Note: UserAuthInfo has language at top level, not nested in preferences
        if (user?.language) {
            return user.language as Locale;
        }

        const stored = localStorage.getItem(LOCALE_STORAGE_KEY);
        if (stored === 'es' || stored === 'en') {
            return stored;
        }

        return 'es';
    };

    const [locale, setLocaleState] = useState<Locale>(getInitialLocale);

    /**
     * Guardar en localStorage cuando cambie
     */
    useEffect(() => {
        localStorage.setItem(LOCALE_STORAGE_KEY, locale);
    }, [locale]);

    /**
     * Sincronizar con preferencias del usuario cuando cambie
     */
    useEffect(() => {
        if (user?.language && user.language !== locale) {
            setLocaleState(user.language as Locale);
        }
    }, [user?.language, locale]);

    /**
     * Cambiar idioma
     */
    const setLocale = (newLocale: Locale) => {
        setLocaleState(newLocale);
    };

    /**
     * Función de traducción
     * @param key - Clave de traducción (ej: 'auth.email')
     * @param params - Parámetros para interpolación (ej: {name: 'Juan'})
     */
    const t = (key: string, params?: Record<string, string>): string => {
        let translation = translations[locale][key] || key;

        // Interpolación de parámetros
        if (params) {
            Object.keys(params).forEach((param) => {
                translation = translation.replace(`{${param}}`, params[param]);
            });
        }

        return translation;
    };

    /**
     * Forzar sincronización con preferencias del usuario
     */
    const syncWithUserPreferences = () => {
        if (user?.language) {
            setLocaleState(user.language as Locale);
        }
    };

    const value: LocaleContextType = {
        locale,
        setLocale,
        t,
        syncWithUserPreferences,
    };

    return <LocaleContext.Provider value={value}>{children}</LocaleContext.Provider>;
};

/**
 * Hook para usar el contexto de idioma
 */
export const useLocale = (): LocaleContextType => {
    const context = useContext(LocaleContext);
    if (context === undefined) {
        throw new Error('useLocale must be used within a LocaleProvider');
    }
    return context;
};

