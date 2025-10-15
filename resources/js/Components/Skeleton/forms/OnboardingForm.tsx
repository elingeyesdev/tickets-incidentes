/**
 * OnboardingFormSkeleton - Skeleton específico para flujo de onboarding
 * Incluye badge, título, formulario y botones con separador
 * 
 * @example
 * <OnboardingFormSkeleton />
 * <OnboardingFormSkeleton fields={4} columns={2} />
 */

import React from 'react';
import clsx from 'clsx';
import { Skeleton } from '../base/Skeleton';
import { InputSkeleton } from '../base/Input';
import { ButtonSkeleton } from '../base/Button';
import { BadgeSkeleton } from '../base/Badge';

interface OnboardingFormSkeletonProps {
    /** Número de campos a mostrar */
    fields?: number;
    /** Número de columnas (1 o 2) */
    columns?: 1 | 2;
}

export const OnboardingFormSkeleton: React.FC<OnboardingFormSkeletonProps> = ({ 
    fields = 3, 
    columns = 2 
}) => {
    return (
        <div className="max-w-3xl mx-auto">
            {/* Card */}
            <div className="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-8">
                {/* Header */}
                <div className="text-center mb-8">
                    <BadgeSkeleton className="mx-auto mb-6 w-40" />
                    <Skeleton className="h-9 w-72 mx-auto mb-3" />
                    <Skeleton className="h-5 w-96 mx-auto" />
                </div>

                {/* Form fields */}
                <div className={clsx(
                    'space-y-6 mb-8',
                    columns === 2 && 'md:grid md:grid-cols-2 md:gap-4 md:space-y-0'
                )}>
                    {Array.from({ length: fields }).map((_, index) => (
                        <InputSkeleton key={index} withLabel />
                    ))}
                </div>

                {/* Separador */}
                <div className="border-t border-gray-200 dark:border-gray-700 pt-6 mt-8">
                    {/* Botones */}
                    <div className="flex items-center justify-between gap-4">
                        <Skeleton className="h-4 w-32" /> {/* Omitir link */}
                        <ButtonSkeleton className="w-40" />
                    </div>
                    
                    {/* Hint */}
                    <Skeleton className="h-3 w-64 mx-auto mt-4" />
                </div>
            </div>
        </div>
    );
};

