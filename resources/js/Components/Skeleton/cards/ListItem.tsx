/**
 * ListItemSkeleton - Skeleton para items de lista
 * Útil para listas de usuarios, tickets, notificaciones, etc.
 * 
 * @example
 * <ListItemSkeleton />
 * <ListItemSkeleton withActions />
 */

import React from 'react';
import clsx from 'clsx';
import { Skeleton } from '../base/Skeleton';
import { AvatarSkeleton } from '../base/Avatar';

interface ListItemSkeletonProps {
    /** Mostrar avatar/icono */
    withAvatar?: boolean;
    /** Mostrar botones de acción */
    withActions?: boolean;
    /** Clases adicionales */
    className?: string;
}

export const ListItemSkeleton: React.FC<ListItemSkeletonProps> = ({ 
    withAvatar = true, 
    withActions = false, 
    className 
}) => (
    <div className={clsx(
        'flex items-center gap-4 p-4 border-b border-gray-200 dark:border-gray-700',
        className
    )}>
        {withAvatar && <AvatarSkeleton size="md" />}
        <div className="flex-1 space-y-2">
            <Skeleton className="h-5 w-48" />
            <Skeleton className="h-4 w-72" />
        </div>
        {withActions && (
            <div className="flex gap-2">
                <Skeleton className="w-8 h-8 rounded-lg" />
                <Skeleton className="w-8 h-8 rounded-lg" />
            </div>
        )}
    </div>
);

