import { View, Text, ScrollView, TouchableOpacity } from 'react-native';
import { useRouter } from 'expo-router';
import { SafeAreaView } from 'react-native-safe-area-context';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { useAuthStore } from '../../../stores/authStore';

export default function HomeScreen() {
    const router = useRouter();
    const user = useAuthStore((state) => state.user);

    const quickActions = [
        {
            title: 'Crear Ticket',
            icon: 'plus-circle' as const,
            color: 'bg-blue-100',
            iconColor: '#2563eb',
            route: '/(tabs)/tickets/create',
        },
        {
            title: 'Mis Tickets',
            icon: 'ticket-confirmation' as const,
            color: 'bg-purple-100',
            iconColor: '#9333ea',
            route: '/(tabs)/tickets',
        },
        {
            title: 'Empresas',
            icon: 'domain' as const,
            color: 'bg-green-100',
            iconColor: '#16a34a',
            route: '/(tabs)/companies',
        },
        {
            title: 'Ayuda',
            icon: 'help-circle' as const,
            color: 'bg-orange-100',
            iconColor: '#ea580c',
            route: '/(tabs)/content',
        },
    ];

    return (
        <SafeAreaView className="flex-1 bg-gray-50">
            <ScrollView contentContainerStyle={{ padding: 24 }}>
                {/* Header */}
                <View className="mb-8">
                    <Text className="text-gray-500 text-lg">Hola,</Text>
                    <Text className="text-3xl font-bold text-gray-900">
                        {user?.profile.displayName || 'Usuario'}
                    </Text>
                </View>

                {/* Quick Actions */}
                <Text className="text-lg font-bold text-gray-900 mb-4">Acciones Rápidas</Text>
                <View className="flex-row flex-wrap justify-between">
                    {quickActions.map((action, index) => (
                        <TouchableOpacity
                            key={index}
                            className="w-[48%] bg-white p-4 rounded-xl mb-4 shadow-sm border border-gray-100 items-center"
                            onPress={() => router.push(action.route as any)}
                        >
                            <View className={`p-3 rounded-full mb-3 ${action.color}`}>
                                <MaterialCommunityIcons
                                    name={action.icon}
                                    size={24}
                                    color={action.iconColor}
                                />
                            </View>
                            <Text className="font-medium text-gray-900">{action.title}</Text>
                        </TouchableOpacity>
                    ))}
                </View>

                {/* Recent Activity Section Placeholder */}
                <View className="mt-4">
                    <View className="flex-row justify-between items-center mb-4">
                        <Text className="text-lg font-bold text-gray-900">Actividad Reciente</Text>
                        <TouchableOpacity onPress={() => router.push('/(tabs)/tickets')}>
                            <Text className="text-blue-600 font-medium">Ver todo</Text>
                        </TouchableOpacity>
                    </View>

                    <View className="bg-white p-6 rounded-xl border border-gray-100 items-center justify-center">
                        <MaterialCommunityIcons name="clipboard-text-outline" size={48} color="#d1d5db" />
                        <Text className="text-gray-500 mt-2 text-center">
                            Tus tickets recientes aparecerán aquí
                        </Text>
                    </View>
                </View>
            </ScrollView>
        </SafeAreaView>
    );
}
