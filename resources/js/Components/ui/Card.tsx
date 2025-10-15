/**
 * Card Component - Contenedor reutilizable
 * Soporta header, footer y múltiples variantes
 */

import React, { HTMLAttributes } from 'react';
import clsx from 'clsx';

interface CardProps extends HTMLAttributes<HTMLDivElement> {
    children: React.ReactNode;
    padding?: 'none' | 'sm' | 'md' | 'lg';
    shadow?: 'none' | 'sm' | 'md' | 'lg';
}

interface CardHeaderProps extends HTMLAttributes<HTMLDivElement> {
    children: React.ReactNode;
}

interface CardBodyProps extends HTMLAttributes<HTMLDivElement> {
    children: React.ReactNode;
}

interface CardFooterProps extends HTMLAttributes<HTMLDivElement> {
    children: React.ReactNode;
}

const paddingStyles = {
    none: 'p-0',
    sm: 'p-3',
    md: 'p-4',
    lg: 'p-6',
};

const shadowStyles = {
    none: 'shadow-none',
    sm: 'shadow-sm',
    md: 'shadow-md',
    lg: 'shadow-lg',
};

export const Card: React.FC<CardProps> & {
    Header: React.FC<CardHeaderProps>;
    Body: React.FC<CardBodyProps>;
    Footer: React.FC<CardFooterProps>;
} = ({ children, padding = 'md', shadow = 'md', className, ...props }) => {
    return (
        <div
            className={clsx(
                'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg',
                paddingStyles[padding],
                shadowStyles[shadow],
                className
            )}
            {...props}
        >
            {children}
        </div>
    );
};

Card.Header = ({ children, className, ...props }: CardHeaderProps) => {
    return (
        <div
            className={clsx(
                'border-b border-gray-200 dark:border-gray-700 pb-4 mb-4',
                className
            )}
            {...props}
        >
            {children}
        </div>
    );
};

Card.Body = ({ children, className, ...props }: CardBodyProps) => {
    return (
        <div className={className} {...props}>
            {children}
        </div>
    );
};

Card.Footer = ({ children, className, ...props }: CardFooterProps) => {
    return (
        <div
            className={clsx(
                'border-t border-gray-200 dark:border-gray-700 pt-4 mt-4',
                className
            )}
            {...props}
        >
            {children}
        </div>
    );
};

