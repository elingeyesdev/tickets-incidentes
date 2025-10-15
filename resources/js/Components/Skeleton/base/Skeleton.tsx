/**
 * Skeleton Component - Componente Base
 * Skeleton loading con animación shimmer profesional
 * 
 * @example
 * <Skeleton className="h-4 w-full" />
 * <Skeleton variant="circular" className="w-12 h-12" />
 * <Skeleton variant="text" lines={3} />
 */

import React from 'react';
import clsx from 'clsx';

interface SkeletonProps {
    /** Variante del skeleton */
    variant?: 'rectangular' | 'circular' | 'rounded' | 'text';
    /** Clases adicionales de Tailwind */
    className?: string;
    /** Número de líneas (solo para variant="text") */
    lines?: number;
    /** Ancho de la última línea (solo para variant="text") */
    lastLineWidth?: string;
}

/**
 * Componente base Skeleton
 * Renderiza un placeholder animado con efecto shimmer
 */
export const Skeleton: React.FC<SkeletonProps> = ({ 
    variant = 'rectangular', 
    className = '',
    lines = 1,
    lastLineWidth = '60%'
}) => {
    const baseClasses = clsx(
        'bg-gray-200 dark:bg-gray-700',
        'relative overflow-hidden',
        'before:absolute before:inset-0',
        'before:-translate-x-full',
        'before:animate-[shimmer_2s_infinite]',
        'before:bg-gradient-to-r',
        'before:from-transparent before:via-white/20 before:to-transparent',
        'dark:before:via-white/10',
    );

    const variantClasses = {
        rectangular: 'rounded-md',
        circular: 'rounded-full',
        rounded: 'rounded-lg',
        text: 'rounded-md h-4',
    };

    // Si es variante "text" con múltiples líneas
    if (variant === 'text' && lines > 1) {
        return (
            <div className="space-y-3">
                {Array.from({ length: lines }).map((_, index) => (
                    <div
                        key={index}
                        className={clsx(
                            baseClasses,
                            variantClasses.text,
                            className,
                            index === lines - 1 && `w-[${lastLineWidth}]`
                        )}
                        style={index === lines - 1 ? { width: lastLineWidth } : undefined}
                    />
                ))}
            </div>
        );
    }

    return (
        <div
            className={clsx(
                baseClasses,
                variantClasses[variant],
                className
            )}
        />
    );
};

