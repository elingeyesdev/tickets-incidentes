/**
 * CardGridSkeleton - Grid de múltiples cards
 * Renderiza una cuadrícula responsive de skeletons de cards
 * 
 * @example
 * <CardGridSkeleton />
 * <CardGridSkeleton count={6} columns={3} />
 * <CardGridSkeleton cardProps={{ withImage: true, withActions: true }} />
 */

import React from 'react';
import clsx from 'clsx';
import { CardSkeleton } from './Card';

interface CardGridSkeletonProps {
    /** Número de cards a mostrar */
    count?: number;
    /** Número de columnas en desktop */
    columns?: 1 | 2 | 3 | 4;
    /** Props a pasar a cada CardSkeleton */
    cardProps?: Omit<React.ComponentProps<typeof CardSkeleton>, 'className'>;
}

export const CardGridSkeleton: React.FC<CardGridSkeletonProps> = ({ 
    count = 6, 
    columns = 3, 
    cardProps = {} 
}) => {
    const gridClasses = {
        1: 'grid-cols-1',
        2: 'grid-cols-1 md:grid-cols-2',
        3: 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
        4: 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
    };

    return (
        <div className={clsx('grid gap-6', gridClasses[columns])}>
            {Array.from({ length: count }).map((_, index) => (
                <CardSkeleton key={index} {...cardProps} />
            ))}
        </div>
    );
};

