/**
 * BadgeSkeleton - Skeleton para badges/etiquetas
 * 
 * @example
 * <BadgeSkeleton />
 * <BadgeSkeleton className="w-32" />
 */

import React from 'react';
import clsx from 'clsx';
import { Skeleton } from './Skeleton';

interface BadgeSkeletonProps {
    /** Clases adicionales */
    className?: string;
}

export const BadgeSkeleton: React.FC<BadgeSkeletonProps> = ({ className }) => (
    <Skeleton className={clsx('h-6 w-20 rounded-md', className)} />
);

