/**
 * Input Component - Reutilizable y Profesional
 * Soporta labels, errores, íconos y tipos
 */

import React, { InputHTMLAttributes, forwardRef } from 'react';
import clsx from 'clsx';

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
    label?: string;
    error?: string;
    helperText?: string;
    leftIcon?: React.ReactNode;
    rightIcon?: React.ReactNode;
    fullWidth?: boolean;
}

export const Input = forwardRef<HTMLInputElement, InputProps>(
    (
        {
            label,
            error,
            helperText,
            leftIcon,
            rightIcon,
            fullWidth = true,
            className,
            disabled,
            ...props
        },
        ref
    ) => {
        return (
            <div className={clsx('flex flex-col', fullWidth && 'w-full')}>
                {/* Label */}
                {label && (
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {label}
                        {props.required && <span className="text-red-500 ml-1">*</span>}
                    </label>
                )}

                {/* Input Container */}
                <div className="relative">
                    {/* Left Icon */}
                    {leftIcon && (
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            {leftIcon}
                        </div>
                    )}

                    {/* Input */}
                    <input
                        ref={ref}
                        className={clsx(
                            // Base styles
                            'block w-full rounded-lg border shadow-sm transition-colors duration-200',
                            'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                            'disabled:bg-gray-100 disabled:cursor-not-allowed dark:disabled:bg-gray-800',
                            // Padding
                            leftIcon ? 'pl-10' : 'pl-3',
                            rightIcon ? 'pr-10' : 'pr-3',
                            'py-2',
                            // Colors
                            error
                                ? 'border-red-300 dark:border-red-600 focus:ring-red-500'
                                : 'border-gray-300 dark:border-gray-600',
                            'bg-white dark:bg-gray-900',
                            'text-gray-900 dark:text-gray-100',
                            'placeholder-gray-400 dark:placeholder-gray-500',
                            // Custom className
                            className
                        )}
                        disabled={disabled}
                        {...props}
                    />

                    {/* Right Icon */}
                    {rightIcon && (
                        <div className="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400">
                            {rightIcon}
                        </div>
                    )}
                </div>

                {/* Error Message */}
                {error && (
                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">{error}</p>
                )}

                {/* Helper Text */}
                {helperText && !error && (
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">{helperText}</p>
                )}
            </div>
        );
    }
);

Input.displayName = 'Input';

