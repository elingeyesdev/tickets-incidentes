import { z } from 'zod';

export const loginSchema = z.object({
    email: z.string().email('Email inválido'),
    password: z.string().min(8, 'La contraseña debe tener al menos 8 caracteres'),
    rememberDevice: z.boolean().optional(),
});

export type LoginFormData = z.infer<typeof loginSchema>;

export const registerSchema = z.object({
    email: z.string().email('Email inválido'),
    password: z.string()
        .min(8, 'Mínimo 8 caracteres')
        .regex(/[A-Za-z]/, 'Debe contener letras')
        .regex(/[0-9]/, 'Debe contener números')
        .regex(/[^A-Za-z0-9]/, 'Debe contener símbolos'),
    confirmPassword: z.string(),
    firstName: z.string().min(2, 'Mínimo 2 caracteres'),
    lastName: z.string().min(2, 'Mínimo 2 caracteres'),
    termsAccepted: z.boolean().refine(val => val === true, {
        message: 'Debes aceptar los términos y condiciones',
    }),
    privacyAccepted: z.boolean().refine(val => val === true, {
        message: 'Debes aceptar la política de privacidad',
    }),
}).refine((data) => data.password === data.confirmPassword, {
    message: "Las contraseñas no coinciden",
    path: ["confirmPassword"],
});

export type RegisterFormData = z.infer<typeof registerSchema>;
