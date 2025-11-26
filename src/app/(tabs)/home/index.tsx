import { View, Text, ScrollView, TouchableOpacity } from 'react-native';
import { useRouter } from 'expo-router';
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
            route: '/(tabs)/tickets/create', // Verify this route exists or update
        },
        {
            title: 'Mis Tickets',
            icon: 'ticket-confirmation' as const,
            color: 'bg-purple-100',
            iconColor: '#9333ea',
            route: '/(tabs)/tickets',
        },
        {
            title: 'Anuncios Recientes',
            icon: 'bullhorn' as const,
            color: 'bg-orange-100',
            iconColor: '#f97316',
            route: '/(tabs)/announcements',
        },
        {
            title: 'Centro de Ayuda',
            icon: 'help-circle' as const,
            color: 'bg-teal-100',
            iconColor: '#14b8a6',
            route: '/(tabs)/help',
        },
    ];

    return (
        <View className="flex-1 bg-gray-50">
            <ScrollView contentContainerStyle={{ padding: 24 }}>
                {/* Header */}
                <View className="mb-8 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-6 border border-blue-100">
                    <View className="flex-row items-center justify-between">
                        <View className="flex-1">
                            <Text className="text-blue-600 text-sm font-semibold mb-1">Bienvenido,</Text>
                            <Text className="text-3xl font-bold text-gray-900">
                                {user?.firstName}
                            </Text>
                        </View>
                        <MaterialCommunityIcons name="hand-wave" size={40} color="#2563eb" />
                    </View>
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
        </View>
    );
}
