/**
 * CardSkeleton - Skeleton para cards/tarjetas
 * Soporta múltiples variantes y configuraciones
 * 
 * @example
 * <CardSkeleton />
 * <CardSkeleton withImage withActions />
 * <CardSkeleton variant="horizontal" />
 */

import React from 'react';
import clsx from 'clsx';
import { Skeleton } from '../base/Skeleton';
import { AvatarSkeleton } from '../base/Avatar';
import { ButtonSkeleton } from '../base/Button';
import { BadgeSkeleton } from '../base/Badge';

interface CardSkeletonProps {
    /** Mostrar imagen/avatar en el card */
    withImage?: boolean;
    /** Mostrar badge en el header */
    withBadge?: boolean;
    /** Mostrar botones de acción */
    withActions?: boolean;
    /** Número de líneas de texto */
    lines?: number;
    /** Variante del card */
    variant?: 'default' | 'horizontal' | 'compact';
    /** Clases adicionales */
    className?: string;
}

export const CardSkeleton: React.FC<CardSkeletonProps> = ({
    withImage = false,
    withBadge = false,
    withActions = false,
    lines = 3,
    variant = 'default',
    className,
}) => {
    if (variant === 'horizontal') {
        return (
            <div className={clsx(
                'bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4',
                'flex gap-4',
                className
            )}>
                {withImage && <Skeleton className="w-24 h-24 rounded-lg flex-shrink-0" />}
                <div className="flex-1 space-y-3">
                    {withBadge && <BadgeSkeleton />}
                    <Skeleton className="h-6 w-3/4" />
                    <Skeleton variant="text" lines={lines} lastLineWidth="80%" />
                    {withActions && (
                        <div className="flex gap-2 pt-2">
                            <ButtonSkeleton className="w-24" />
                            <ButtonSkeleton className="w-24" />
                        </div>
                    )}
                </div>
            </div>
        );
    }

    if (variant === 'compact') {
        return (
            <div className={clsx(
                'bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4',
                'space-y-3',
                className
            )}>
                <div className="flex items-center gap-3">
                    <AvatarSkeleton size="sm" />
                    <div className="flex-1">
                        <Skeleton className="h-4 w-32" />
                    </div>
                    {withBadge && <BadgeSkeleton />}
                </div>
                <Skeleton variant="text" lines={2} lastLineWidth="70%" />
            </div>
        );
    }

    // Variant: default
    return (
        <div className={clsx(
            'bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden',
            className
        )}>
            {withImage && <Skeleton className="w-full h-48 rounded-none" />}
            
            <div className="p-6 space-y-4">
                <div className="flex items-start justify-between">
                    <div className="flex-1 space-y-2">
                        <Skeleton className="h-6 w-3/4" />
                        {withBadge && <BadgeSkeleton />}
                    </div>
                </div>
                
                <Skeleton variant="text" lines={lines} lastLineWidth="60%" />
                
                {withActions && (
                    <div className="flex gap-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                        <ButtonSkeleton className="w-28" />
                        <ButtonSkeleton className="w-28" />
                    </div>
                )}
            </div>
        </div>
    );
};

