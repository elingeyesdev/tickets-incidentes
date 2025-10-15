/**
 * ButtonSkeleton - Skeleton para botones
 * 
 * @example
 * <ButtonSkeleton />
 * <ButtonSkeleton fullWidth />
 */

import React from 'react';
import clsx from 'clsx';
import { Skeleton } from './Skeleton';

interface ButtonSkeletonProps {
    /** Clases adicionales */
    className?: string;
    /** Ocupar todo el ancho disponible */
    fullWidth?: boolean;
}

export const ButtonSkeleton: React.FC<ButtonSkeletonProps> = ({ 
    className, 
    fullWidth = false 
}) => (
    <Skeleton 
        className={clsx(
            'h-12 rounded-lg',
            fullWidth ? 'w-full' : 'w-32',
            className
        )} 
    />
);

