/**
 * NotificationContext - Gestión Global de Notificaciones
 * 
 * Responsabilidades:
 * - Toast notifications
 * - Mensajes de éxito/error
 * - Auto-dismiss
 */

import React, { createContext, useContext, useState, useCallback, ReactNode } from 'react';

type NotificationType = 'success' | 'error' | 'info' | 'warning';

interface Notification {
    id: string;
    type: NotificationType;
    message: string;
    duration?: number;
}

interface NotificationContextType {
    notifications: Notification[];
    showNotification: (
        type: NotificationType,
        message: string,
        duration?: number
    ) => void;
    success: (message: string, duration?: number) => void;
    error: (message: string, duration?: number) => void;
    info: (message: string, duration?: number) => void;
    warning: (message: string, duration?: number) => void;
    removeNotification: (id: string) => void;
}

const NotificationContext = createContext<NotificationContextType | undefined>(undefined);

interface NotificationProviderProps {
    children: ReactNode;
}

export const NotificationProvider: React.FC<NotificationProviderProps> = ({ children }) => {
    const [notifications, setNotifications] = useState<Notification[]>([]);

    /**
     * Agregar notificación
     */
    const showNotification = useCallback(
        (type: NotificationType, message: string, duration = 5000) => {
            const id = Math.random().toString(36).substring(7);
            const notification: Notification = { id, type, message, duration };

            setNotifications((prev) => [...prev, notification]);

            // Auto-dismiss
            if (duration > 0) {
                setTimeout(() => {
                    removeNotification(id);
                }, duration);
            }
        },
        []
    );

    /**
     * Remover notificación
     */
    const removeNotification = useCallback((id: string) => {
        setNotifications((prev) => prev.filter((n) => n.id !== id));
    }, []);

    /**
     * Helpers para tipos específicos
     */
    const success = useCallback(
        (message: string, duration?: number) => {
            showNotification('success', message, duration);
        },
        [showNotification]
    );

    const error = useCallback(
        (message: string, duration?: number) => {
            showNotification('error', message, duration);
        },
        [showNotification]
    );

    const info = useCallback(
        (message: string, duration?: number) => {
            showNotification('info', message, duration);
        },
        [showNotification]
    );

    const warning = useCallback(
        (message: string, duration?: number) => {
            showNotification('warning', message, duration);
        },
        [showNotification]
    );

    const value: NotificationContextType = {
        notifications,
        showNotification,
        success,
        error,
        info,
        warning,
        removeNotification,
    };

    return (
        <NotificationContext.Provider value={value}>{children}</NotificationContext.Provider>
    );
};

/**
 * Hook para usar el contexto de notificaciones
 */
export const useNotification = (): NotificationContextType => {
    const context = useContext(NotificationContext);
    if (context === undefined) {
        throw new Error('useNotification must be used within a NotificationProvider');
    }
    return context;
};

