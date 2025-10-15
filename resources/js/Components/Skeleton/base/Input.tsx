/**
 * InputSkeleton - Skeleton para campos de input
 * 
 * @example
 * <InputSkeleton />
 * <InputSkeleton withLabel={false} />
 */

import React from 'react';
import { Skeleton } from './Skeleton';

interface InputSkeletonProps {
    /** Clases adicionales */
    className?: string;
    /** Mostrar label arriba del input */
    withLabel?: boolean;
}

export const InputSkeleton: React.FC<InputSkeletonProps> = ({ 
    className, 
    withLabel = true 
}) => (
    <div className={className}>
        {withLabel && <Skeleton className="h-4 w-24 mb-2" />}
        <Skeleton className="h-12 w-full rounded-lg" />
    </div>
);

