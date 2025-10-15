/**
 * Badge Component - Componente para mostrar badges/etiquetas
 */

import React from 'react';

export interface BadgeProps extends React.HTMLAttributes<HTMLSpanElement> {
    variant?: 'default' | 'primary' | 'secondary' | 'success' | 'warning' | 'error';
    children: React.ReactNode;
}

const variantStyles: Record<string, string> = {
    default: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    primary: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    secondary: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
    success: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    error: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
};

export const Badge: React.FC<BadgeProps> = ({ 
    variant = 'default', 
    className = '', 
    children, 
    ...props 
}) => {
    const variantClass = variantStyles[variant] || variantStyles.default;

    return (
        <span
            className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${variantClass} ${className}`}
            {...props}
        >
            {children}
        </span>
    );
};

