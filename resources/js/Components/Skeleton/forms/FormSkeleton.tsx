/**
 * FormSkeleton - Skeleton para formularios completos
 * Componente modular que se adapta a diferentes layouts
 * 
 * @example
 * <FormSkeleton fields={5} withButton />
 * <FormSkeleton layout="grid" columns={2} />
 */

import React from 'react';
import clsx from 'clsx';
import { Skeleton } from '../base/Skeleton';
import { InputSkeleton } from '../base/Input';
import { ButtonSkeleton } from '../base/Button';
import { BadgeSkeleton } from '../base/Badge';

interface FormSkeletonProps {
    /** Número de campos a mostrar */
    fields?: number;
    /** Layout del formulario */
    layout?: 'vertical' | 'grid';
    /** Número de columnas (solo para layout="grid") */
    columns?: 2 | 3;
    /** Mostrar botón al final */
    withButton?: boolean;
    /** Mostrar botones múltiples (ej: Cancelar + Guardar) */
    withMultipleButtons?: boolean;
    /** Mostrar header con título */
    withHeader?: boolean;
    /** Clases adicionales */
    className?: string;
}

export const FormSkeleton: React.FC<FormSkeletonProps> = ({
    fields = 3,
    layout = 'vertical',
    columns = 2,
    withButton = true,
    withMultipleButtons = false,
    withHeader = false,
    className,
}) => {
    const gridClasses = layout === 'grid' 
        ? `grid grid-cols-${columns} gap-4`
        : 'space-y-6';

    return (
        <div className={clsx('w-full', className)}>
            {/* Header opcional */}
            {withHeader && (
                <div className="text-center mb-8">
                    <BadgeSkeleton className="mx-auto mb-4" />
                    <Skeleton className="h-8 w-64 mx-auto mb-2" />
                    <Skeleton className="h-4 w-96 mx-auto" />
                </div>
            )}

            {/* Campos del formulario */}
            <div className={gridClasses}>
                {Array.from({ length: fields }).map((_, index) => (
                    <InputSkeleton key={index} withLabel />
                ))}
            </div>

            {/* Botones */}
            {withButton && (
                <div className={clsx(
                    'flex gap-3 mt-8',
                    withMultipleButtons ? 'justify-between' : 'justify-end'
                )}>
                    {withMultipleButtons && (
                        <ButtonSkeleton className="w-32" />
                    )}
                    <ButtonSkeleton className="w-40" />
                </div>
            )}
        </div>
    );
};

