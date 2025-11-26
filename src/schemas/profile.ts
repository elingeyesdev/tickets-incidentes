import { z } from 'zod';

export const profileSchema = z.object({
    firstName: z.string().min(2, 'Mínimo 2 caracteres'),
    lastName: z.string().min(2, 'Mínimo 2 caracteres'),
    phoneNumber: z.string().optional(),
});

export type ProfileFormData = z.infer<typeof profileSchema>;

export const passwordChangeSchema = z.object({
    currentPassword: z.string().min(1, 'Requerido'),
    newPassword: z.string()
        .min(8, 'Mínimo 8 caracteres')
        .regex(/[A-Za-z]/, 'Debe contener letras')
        .regex(/[0-9]/, 'Debe contener números')
        .regex(/[^A-Za-z0-9]/, 'Debe contener símbolos'),
    confirmNewPassword: z.string(),
}).refine((data) => data.newPassword === data.confirmNewPassword, {
    message: "Las contraseñas no coinciden",
    path: ["confirmNewPassword"],
});

export type PasswordChangeFormData = z.infer<typeof passwordChangeSchema>;
