/**
 * PageSkeleton - Skeleton para páginas completas
 * Skeleton genérico que se adapta a diferentes layouts de página
 */

import React from 'react';
import { Skeleton } from '../base/Skeleton';
import { CardSkeleton } from '../cards/Card';

interface PageSkeletonProps {
    /** Variante del layout de página */
    variant?: 'dashboard' | 'form' | 'list' | 'detail';
    /** Mostrar header (título + descripción) */
    withHeader?: boolean;
    /** Mostrar breadcrumbs */
    withBreadcrumbs?: boolean;
    /** Número de stats cards en el header (solo dashboard) */
    statsCount?: number;
}

/**
 * PageSkeleton - Dashboard variant
 * Header + Stats cards + Content grid
 */
const DashboardVariant: React.FC<Omit<PageSkeletonProps, 'variant'>> = ({
    withHeader = true,
    withBreadcrumbs = false,
    statsCount = 3,
}) => (
    <div className="space-y-6">
        {withBreadcrumbs && (
            <div className="flex items-center space-x-2">
                <Skeleton className="h-4 w-16" />
                <Skeleton className="h-4 w-4" />
                <Skeleton className="h-4 w-24" />
            </div>
        )}

        {withHeader && (
            <div className="space-y-2">
                <Skeleton className="h-8 w-64" /> {/* Título */}
                <Skeleton className="h-4 w-96" /> {/* Descripción */}
            </div>
        )}

        {/* Stats cards */}
        <div className={`grid grid-cols-1 md:grid-cols-${statsCount} gap-4`}>
            {Array.from({ length: statsCount }).map((_, index) => (
                <div
                    key={index}
                    className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-3"
                >
                    <Skeleton className="h-4 w-24" /> {/* Label */}
                    <Skeleton className="h-8 w-32" /> {/* Value */}
                    <Skeleton className="h-3 w-20" /> {/* Subtitle */}
                </div>
            ))}
        </div>

        {/* Content grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <CardSkeleton />
            <CardSkeleton />
            <CardSkeleton />
        </div>
    </div>
);

/**
 * PageSkeleton - Form variant
 * Header + Form fields
 */
const FormVariant: React.FC<Omit<PageSkeletonProps, 'variant'>> = ({
    withHeader = true,
    withBreadcrumbs = false,
}) => (
    <div className="space-y-6">
        {withBreadcrumbs && (
            <div className="flex items-center space-x-2">
                <Skeleton className="h-4 w-16" />
                <Skeleton className="h-4 w-4" />
                <Skeleton className="h-4 w-24" />
            </div>
        )}

        <div className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-6">
            {withHeader && (
                <div className="space-y-2 pb-4 border-b border-gray-200 dark:border-gray-700">
                    <Skeleton className="h-7 w-48" /> {/* Título */}
                    <Skeleton className="h-4 w-96" /> {/* Descripción */}
                </div>
            )}

            {/* Form fields */}
            <div className="space-y-4">
                <div className="space-y-2">
                    <Skeleton className="h-4 w-32" /> {/* Label */}
                    <Skeleton className="h-10 w-full" /> {/* Input */}
                </div>
                <div className="space-y-2">
                    <Skeleton className="h-4 w-32" />
                    <Skeleton className="h-10 w-full" />
                </div>
                <div className="space-y-2">
                    <Skeleton className="h-4 w-32" />
                    <Skeleton className="h-24 w-full" /> {/* Textarea */}
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-2">
                        <Skeleton className="h-4 w-24" />
                        <Skeleton className="h-10 w-full" />
                    </div>
                    <div className="space-y-2">
                        <Skeleton className="h-4 w-24" />
                        <Skeleton className="h-10 w-full" />
                    </div>
                </div>
            </div>

            {/* Actions */}
            <div className="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <Skeleton className="h-10 w-24" /> {/* Cancel button */}
                <Skeleton className="h-10 w-32" /> {/* Submit button */}
            </div>
        </div>
    </div>
);

/**
 * PageSkeleton - List variant
 * Header + Search bar + List items
 */
const ListVariant: React.FC<Omit<PageSkeletonProps, 'variant'>> = ({
    withHeader = true,
    withBreadcrumbs = false,
}) => (
    <div className="space-y-6">
        {withBreadcrumbs && (
            <div className="flex items-center space-x-2">
                <Skeleton className="h-4 w-16" />
                <Skeleton className="h-4 w-4" />
                <Skeleton className="h-4 w-24" />
            </div>
        )}

        {withHeader && (
            <div className="flex items-center justify-between">
                <div className="space-y-2">
                    <Skeleton className="h-8 w-48" /> {/* Título */}
                    <Skeleton className="h-4 w-64" /> {/* Descripción */}
                </div>
                <Skeleton className="h-10 w-32" /> {/* Action button */}
            </div>
        )}

        {/* Search bar */}
        <div className="flex items-center space-x-3">
            <Skeleton className="h-10 flex-1" /> {/* Search input */}
            <Skeleton className="h-10 w-32" /> {/* Filter button */}
        </div>

        {/* List items */}
        <div className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-200 dark:divide-gray-700">
            {Array.from({ length: 5 }).map((_, index) => (
                <div key={index} className="p-4 flex items-center space-x-4">
                    <Skeleton variant="circular" className="w-10 h-10" />
                    <div className="flex-1 space-y-2">
                        <Skeleton className="h-5 w-48" />
                        <Skeleton className="h-4 w-96" />
                    </div>
                    <Skeleton className="h-8 w-20" /> {/* Badge */}
                    <Skeleton className="h-8 w-8" /> {/* Action icon */}
                </div>
            ))}
        </div>

        {/* Pagination */}
        <div className="flex items-center justify-between">
            <Skeleton className="h-4 w-32" /> {/* Results text */}
            <div className="flex items-center space-x-2">
                <Skeleton className="h-8 w-8" />
                <Skeleton className="h-8 w-8" />
                <Skeleton className="h-8 w-8" />
                <Skeleton className="h-8 w-8" />
            </div>
        </div>
    </div>
);

/**
 * PageSkeleton - Detail variant
 * Header + Main content + Sidebar
 */
const DetailVariant: React.FC<Omit<PageSkeletonProps, 'variant'>> = ({
    withHeader = true,
    withBreadcrumbs = false,
}) => (
    <div className="space-y-6">
        {withBreadcrumbs && (
            <div className="flex items-center space-x-2">
                <Skeleton className="h-4 w-16" />
                <Skeleton className="h-4 w-4" />
                <Skeleton className="h-4 w-24" />
                <Skeleton className="h-4 w-4" />
                <Skeleton className="h-4 w-32" />
            </div>
        )}

        {withHeader && (
            <div className="flex items-start justify-between">
                <div className="space-y-3">
                    <Skeleton className="h-8 w-64" /> {/* Título */}
                    <div className="flex items-center space-x-3">
                        <Skeleton className="h-6 w-20" /> {/* Badge */}
                        <Skeleton className="h-4 w-32" /> {/* Timestamp */}
                    </div>
                </div>
                <div className="flex space-x-2">
                    <Skeleton className="h-10 w-24" />
                    <Skeleton className="h-10 w-24" />
                </div>
            </div>
        )}

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {/* Main content */}
            <div className="lg:col-span-2 space-y-6">
                <div className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-4">
                    <Skeleton className="h-6 w-32" /> {/* Section title */}
                    <Skeleton variant="text" lines={5} />
                </div>

                <div className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-4">
                    <Skeleton className="h-6 w-32" />
                    <Skeleton variant="text" lines={3} />
                </div>
            </div>

            {/* Sidebar */}
            <div className="space-y-6">
                <div className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-3">
                    <Skeleton className="h-5 w-24" />
                    <div className="space-y-2">
                        <Skeleton className="h-4 w-full" />
                        <Skeleton className="h-4 w-full" />
                        <Skeleton className="h-4 w-3/4" />
                    </div>
                </div>

                <div className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-3">
                    <Skeleton className="h-5 w-24" />
                    <div className="space-y-2">
                        <Skeleton className="h-4 w-full" />
                        <Skeleton className="h-4 w-full" />
                    </div>
                </div>
            </div>
        </div>
    </div>
);

/**
 * PageSkeleton - Main component
 */
export const PageSkeleton: React.FC<PageSkeletonProps> = ({
    variant = 'dashboard',
    ...props
}) => {
    switch (variant) {
        case 'dashboard':
            return <DashboardVariant {...props} />;
        case 'form':
            return <FormVariant {...props} />;
        case 'list':
            return <ListVariant {...props} />;
        case 'detail':
            return <DetailVariant {...props} />;
        default:
            return <DashboardVariant {...props} />;
    }
};