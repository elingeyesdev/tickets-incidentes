import { View, Text } from 'react-native';
import { Link, useRouter } from 'expo-router';
import { Button } from 'react-native-paper';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Logo } from '@/components/ui/Logo';

export default function WelcomeScreen() {
    const router = useRouter();

    return (
        <SafeAreaView className="flex-1 bg-white">
            <View className="flex-1 items-center justify-center px-6">
                <View className="mb-12">
                    <Logo size="lg" />
                </View>

                <Text className="text-3xl font-bold text-center text-gray-900 mb-4">
                    Bienvenido a Helpdesk
                </Text>

                <Text className="text-center text-gray-500 text-lg mb-12 leading-relaxed">
                    Gestiona tus tickets de soporte, sigue a tus empresas favoritas y mantente informado en un solo lugar.
                </Text>

                <View className="w-full space-y-4 gap-4">
                    <Button
                        mode="contained"
                        onPress={() => router.push('/(auth)/login')}
                        contentStyle={{ height: 56 }}
                        labelStyle={{ fontSize: 18, fontWeight: 'bold' }}
                        className="rounded-xl bg-blue-600"
                    >
                        Iniciar Sesión
                    </Button>

                    <Button
                        mode="outlined"
                        onPress={() => router.push('/(auth)/register')}
                        contentStyle={{ height: 56 }}
                        labelStyle={{ fontSize: 18, fontWeight: 'bold', color: '#2563eb' }}
                        className="rounded-xl border-blue-600"
                    >
                        Crear Cuenta
                    </Button>
                </View>

                <Link href="/(tabs)/companies" asChild>
                    <Button
                        mode="text"
                        className="mt-8"
                        labelStyle={{ color: '#6b7280' }}
                    >
                        Explorar sin cuenta
                    </Button>
                </Link>
            </View>
        </SafeAreaView>
    );
}
