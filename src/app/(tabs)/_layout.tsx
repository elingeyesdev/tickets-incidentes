import { Tabs } from 'expo-router';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { useTheme } from 'react-native-paper';

export default function TabsLayout() {
    const theme = useTheme();

    return (
        <Tabs
            screenOptions={{
                headerShown: false,
                tabBarActiveTintColor: '#007bff',
                tabBarInactiveTintColor: '#6c757d',
                tabBarStyle: {
                    borderTopWidth: 1,
                    borderTopColor: '#e5e7eb',
                    height: 60,
                    paddingBottom: 8,
                    paddingTop: 8,
                },
            }}
        >
            <Tabs.Screen
                name="home"
                options={{
                    title: 'Inicio',
                    tabBarIcon: ({ color, size }) => (
                        <MaterialCommunityIcons name="home" size={size} color={color} />
                    ),
                }}
            />
            <Tabs.Screen
                name="tickets"
                options={{
                    title: 'Tickets',
                    tabBarIcon: ({ color, size }) => (
                        <MaterialCommunityIcons name="ticket-confirmation" size={size} color={color} />
                    ),
                }}
            />
            <Tabs.Screen
                name="companies"
                options={{
                    title: 'Empresas',
                    tabBarIcon: ({ color, size }) => (
                        <MaterialCommunityIcons name="domain" size={size} color={color} />
                    ),
                }}
            />
            <Tabs.Screen
                name="content"
                options={{
                    title: 'Contenido',
                    tabBarIcon: ({ color, size }) => (
                        <MaterialCommunityIcons name="newspaper" size={size} color={color} />
                    ),
                }}
            />
            <Tabs.Screen
                name="profile"
                options={{
                    title: 'Perfil',
                    tabBarIcon: ({ color, size }) => (
                        <MaterialCommunityIcons name="account" size={size} color={color} />
                    ),
                }}
            />
        </Tabs>
    );
}
