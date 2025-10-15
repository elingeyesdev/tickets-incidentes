/**
 * AvatarSkeleton - Skeleton circular para avatares
 * 
 * @example
 * <AvatarSkeleton />
 * <AvatarSkeleton size="lg" />
 */

import React from 'react';
import clsx from 'clsx';
import { Skeleton } from './Skeleton';

interface AvatarSkeletonProps {
    /** Tamaño del avatar */
    size?: 'sm' | 'md' | 'lg';
    /** Clases adicionales */
    className?: string;
}

export const AvatarSkeleton: React.FC<AvatarSkeletonProps> = ({ 
    size = 'md',
    className 
}) => {
    const sizeClasses = {
        sm: 'w-8 h-8',
        md: 'w-12 h-12',
        lg: 'w-16 h-16',
    };

    return (
        <Skeleton 
            variant="circular" 
            className={clsx(sizeClasses[size], className)} 
        />
    );
};

