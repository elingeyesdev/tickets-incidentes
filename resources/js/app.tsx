1/**
 * Main Application Entry Point
 * Integra Inertia.js + React + Apollo Client + Contexts Globales
 */

import './bootstrap';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { ApolloProvider } from '@apollo/client/react';
import { apolloClient } from '@/lib/apollo/client';
import {
    AuthProvider,
    ThemeProvider,
    LocaleProvider,
    NotificationProvider,
} from '@/contexts';

/**
 * Inicialización de Inertia
 *
 * IMPORTANTE: Todos los providers globales (Auth, Theme, Locale, Notification)
 * envuelven la aplicación completa para que estén disponibles en TODAS las páginas.
 */
createInertiaApp({
    title: (title) => (title ? `${title} - Helpdesk` : 'Helpdesk'),

    resolve: (name) => {
        const pages = import.meta.glob<any>('./Pages/**/*.tsx', { eager: true });
        const page = pages[`./Pages/${name}.tsx`];

        if (!page) {
            throw new Error(`Page not found: ${name}`);
        }

        return page.default;
    },

    setup({ el, App, props }) {
        const root = createRoot(el);

        // Envolver la aplicación con TODOS los providers globales
        root.render(
            <ApolloProvider client={apolloClient}>
                <AuthProvider>
                    <ThemeProvider>
                        <LocaleProvider>
                            <NotificationProvider>
                                <App {...props} />
                            </NotificationProvider>
                        </LocaleProvider>
                    </ThemeProvider>
                </AuthProvider>
            </ApolloProvider>
        );
    },

    progress: {
        color: '#4B5563',
        showSpinner: true,
    },
});

