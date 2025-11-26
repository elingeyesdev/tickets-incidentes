import { useState } from 'react';
import { View, Text, TouchableOpacity, Alert } from 'react-native';
import { useRouter, Link } from 'expo-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Button } from 'react-native-paper';
import { SafeAreaView } from 'react-native-safe-area-context';
import { z } from 'zod';
import { client } from '../../services/api/client';
import { ControlledInput } from '../../components/ui/ControlledInput';

const forgotPasswordSchema = z.object({
    email: z.string().email('Email inválido'),
});

type ForgotPasswordData = z.infer<typeof forgotPasswordSchema>;

export default function ForgotPasswordScreen() {
    const router = useRouter();
    const [isLoading, setIsLoading] = useState(false);

    const { control, handleSubmit } = useForm<ForgotPasswordData>({
        resolver: zodResolver(forgotPasswordSchema),
        defaultValues: { email: '' },
    });

    const onSubmit = async (data: ForgotPasswordData) => {
        setIsLoading(true);
        try {
            await client.post('/api/auth/password-reset', data);
            Alert.alert(
                'Enlace enviado',
                'Si el correo existe en nuestro sistema, recibirás un enlace para restablecer tu contraseña.',
                [{ text: 'Volver al Login', onPress: () => router.back() }]
            );
        } catch (error) {
            // Generic success message for security
            Alert.alert(
                'Enlace enviado',
                'Si el correo existe en nuestro sistema, recibirás un enlace para restablecer tu contraseña.',
                [{ text: 'Volver al Login', onPress: () => router.back() }]
            );
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <SafeAreaView className="flex-1 bg-white">
            <View className="flex-1 px-6 pt-12">
                <View className="mb-8">
                    <Text className="text-3xl font-bold text-gray-900">Recuperar Contraseña</Text>
                    <Text className="text-gray-500 mt-2">
                        Ingresa tu correo electrónico y te enviaremos las instrucciones para restablecer tu contraseña.
                    </Text>
                </View>

                <ControlledInput
                    control={control}
                    name="email"
                    label="Correo Electrónico"
                    autoCapitalize="none"
                    keyboardType="email-address"
                />

                <Button
                    mode="contained"
                    onPress={handleSubmit(onSubmit)}
                    loading={isLoading}
                    disabled={isLoading}
                    contentStyle={{ height: 50 }}
                    className="rounded-xl bg-blue-600 mb-6"
                >
                    Enviar Enlace
                </Button>

                <TouchableOpacity onPress={() => router.back()} className="items-center">
                    <Text className="text-blue-600 font-bold">Volver a Iniciar Sesión</Text>
                </TouchableOpacity>
            </View>
        </SafeAreaView>
    );
}
