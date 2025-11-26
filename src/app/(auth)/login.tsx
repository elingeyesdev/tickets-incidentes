import { useState } from 'react';
import { View, Text, TouchableOpacity, Alert } from 'react-native';
import { useRouter, Link } from 'expo-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Button, Checkbox, ActivityIndicator } from 'react-native-paper';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useAuthStore } from '../../stores/authStore';
import { ControlledInput } from '../../components/ui/ControlledInput';
import { loginSchema, LoginFormData } from '../../schemas/auth';
import { Logo } from '../../components/ui/Logo';

export default function LoginScreen() {
    const router = useRouter();
    const login = useAuthStore((state) => state.login);
    const isLoading = useAuthStore((state) => state.isLoading);
    const [showPassword, setShowPassword] = useState(false);

    const { control, handleSubmit } = useForm<LoginFormData>({
        resolver: zodResolver(loginSchema),
        defaultValues: {
            email: '',
            password: '',
            rememberDevice: false,
        },
    });

    const onSubmit = async (data: LoginFormData) => {
        try {
            await login(data.email, data.password);
            // Navigation is handled in the store upon success
        } catch (error: any) {
            const status = error.response?.status;
            const message = error.response?.data?.message || error.message;
            const details = error.response?.data ? JSON.stringify(error.response.data) : '';

            Alert.alert(
                `Error ${status || ''}`,
                `${message}\n${details}`
            );
        }
    };

    return (
        <SafeAreaView className="flex-1 bg-white">
            <View className="flex-1 px-6 justify-center">
                <View className="items-center mb-8">
                    <Logo size="md" />
                    <Text className="text-2xl font-bold mt-6 text-gray-900">Iniciar Sesión</Text>
                    <Text className="text-gray-500 mt-2">Bienvenido de nuevo</Text>
                </View>

                <ControlledInput
                    control={control}
                    name="email"
                    label="Correo Electrónico"
                    autoCapitalize="none"
                    keyboardType="email-address"
                />

                <ControlledInput
                    control={control}
                    name="password"
                    label="Contraseña"
                    secureTextEntry={!showPassword}
                    right={
                        <Button
                            onPress={() => setShowPassword(!showPassword)}
                            compact
                            textColor="#6b7280"
                        >
                            {showPassword ? 'Ocultar' : 'Mostrar'}
                        </Button>
                    }
                />

                <View className="flex-row items-center justify-between mb-6">
                    <View className="flex-row items-center">
                        <Controller
                            control={control}
                            name="rememberDevice"
                            render={({ field: { value, onChange } }) => (
                                <Checkbox.Android
                                    status={value ? 'checked' : 'unchecked'}
                                    onPress={() => onChange(!value)}
                                    color="#2563eb"
                                />
                            )}
                        />
                        <Text className="text-gray-600 ml-1">Recordar dispositivo</Text>
                    </View>

                    <Link href="/(auth)/forgot-password" asChild>
                        <TouchableOpacity>
                            <Text className="text-blue-600 font-medium">¿Olvidaste tu contraseña?</Text>
                        </TouchableOpacity>
                    </Link>
                </View>

                <Button
                    mode="contained"
                    onPress={handleSubmit(onSubmit)}
                    loading={isLoading}
                    disabled={isLoading}
                    contentStyle={{ height: 50 }}
                    className="rounded-xl bg-blue-600 mb-6"
                >
                    Iniciar Sesión
                </Button>

                <View className="flex-row justify-center mt-4">
                    <Text className="text-gray-600">¿No tienes cuenta? </Text>
                    <Link href="/(auth)/register" asChild>
                        <TouchableOpacity>
                            <Text className="text-blue-600 font-bold">Regístrate</Text>
                        </TouchableOpacity>
                    </Link>
                </View>
            </View>
        </SafeAreaView>
    );
}

import { Controller } from 'react-hook-form';
