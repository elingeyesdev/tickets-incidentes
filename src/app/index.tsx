import { useEffect } from 'react';
import { View } from 'react-native';
import { useRouter } from 'expo-router';
import { useAuthStore } from '../stores/authStore';
import { Logo } from '../components/ui/Logo';

export default function SplashScreen() {
    const router = useRouter();
    const checkAuth = useAuthStore((state) => state.checkAuth);
    const isAuthenticated = useAuthStore((state) => state.isAuthenticated);

    useEffect(() => {
        const init = async () => {
            // Minimum splash time of 2 seconds
            const minSplashTime = new Promise((resolve) => setTimeout(resolve, 2000));
            const authCheck = checkAuth();

            await Promise.all([minSplashTime, authCheck]);

            if (useAuthStore.getState().isAuthenticated) {
                router.replace('/(tabs)/home');
            } else {
                router.replace('/(auth)/welcome');
            }
        };

        init();
    }, []);

    return (
        <View className="flex-1 items-center justify-center bg-gray-50">
            <Logo size="lg" />
        </View>
    );
}
