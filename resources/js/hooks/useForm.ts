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

interface GraphQLErrorExtensions {
    field?: string;
    [key: string]: unknown;
}

interface GraphQLErrorWithExtensions {
    message: string;
    extensions?: GraphQLErrorExtensions;
}

interface GraphQLErrorLike {
    graphQLErrors?: GraphQLErrorWithExtensions[];
}

export const useForm = <T extends Record<string, unknown>>({
    initialValues,
    onSubmit,
}: UseFormOptions<T>) => {
    const [data, setData] = useState<T>(initialValues);
    const [errors, setErrors] = useState<FormErrors>({});
    const [processing, setProcessing] = useState(false);
    const [isDirty, setIsDirty] = useState(false);

    const handleChange = <K extends keyof T>(field: K, value: T[K]) => {
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
        } catch (error) {
            // Parse GraphQL errors
            const graphQLError = error as GraphQLErrorLike;
            if (graphQLError?.graphQLErrors) {
                const newErrors: FormErrors = {};
                graphQLError.graphQLErrors.forEach((err: GraphQLErrorWithExtensions) => {
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

