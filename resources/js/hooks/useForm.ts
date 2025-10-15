/**
 * useForm Hook - Gestión de formularios simplificada
 * Similar a Inertia useForm pero para GraphQL
 */

import { useState, FormEvent } from 'react';

interface UseFormOptions<T> {
    initialValues: T;
    onSubmit: (values: T) => void | Promise<void>;
}

interface FormErrors {
    [key: string]: string;
}

export const useForm = <T extends Record<string, any>>({
    initialValues,
    onSubmit,
}: UseFormOptions<T>) => {
    const [data, setData] = useState<T>(initialValues);
    const [errors, setErrors] = useState<FormErrors>({});
    const [processing, setProcessing] = useState(false);
    const [isDirty, setIsDirty] = useState(false);

    const handleChange = (field: keyof T, value: any) => {
        setData((prev) => ({ ...prev, [field]: value }));
        setIsDirty(true);
        // Clear error for this field
        if (errors[field as string]) {
            setErrors((prev) => {
                const newErrors = { ...prev };
                delete newErrors[field as string];
                return newErrors;
            });
        }
    };

    const handleSubmit = async (e?: FormEvent) => {
        if (e) {
            e.preventDefault();
        }

        setProcessing(true);
        setErrors({});

        try {
            await onSubmit(data);
            setIsDirty(false);
        } catch (error: any) {
            // Parse GraphQL errors
            if (error.graphQLErrors) {
                const newErrors: FormErrors = {};
                error.graphQLErrors.forEach((err: any) => {
                    if (err.extensions?.field) {
                        newErrors[err.extensions.field] = err.message;
                    }
                });
                setErrors(newErrors);
            }
        } finally {
            setProcessing(false);
        }
    };

    const reset = () => {
        setData(initialValues);
        setErrors({});
        setIsDirty(false);
        setProcessing(false);
    };

    const setError = (field: keyof T, message: string) => {
        setErrors((prev) => ({ ...prev, [field as string]: message }));
    };

    return {
        data,
        setData: handleChange,
        errors,
        setError,
        processing,
        isDirty,
        handleSubmit,
        reset,
    };
};

