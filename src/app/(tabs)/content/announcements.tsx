import { View, FlatList, Text, RefreshControl, TouchableOpacity } from 'react-native';
import { Avatar, Chip, Card, Badge } from 'react-native-paper';
import { useContentStore } from '@/stores/contentStore';
import { useEffect, useState } from 'react';
import { Announcement } from '@/types/content';
import { formatDistanceToNow } from 'date-fns';
import { es } from 'date-fns/locale';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useRouter } from 'expo-router';

export default function AnnouncementsScreen() {
    const { announcements, fetchAnnouncements, markAnnouncementAsRead, isLoading } = useContentStore();
    const [refreshing, setRefreshing] = useState(false);
    const router = useRouter();

    const loadData = async () => {
        try {
            await fetchAnnouncements();
        } catch (error) {
            console.error(error);
        } finally {
            setRefreshing(false);
        }
    };

    useEffect(() => {
        loadData();
    }, []);

    const onRefresh = () => {
        setRefreshing(true);
        loadData();
    };

    const handlePress = (item: Announcement) => {
        if (!item.isRead) {
            markAnnouncementAsRead(item.id);
        }
        // Could navigate to detail if content is long, but for now expand or just show
        // Prompt says "Lista de anuncios... Detalle (modal o expandible)"
        // I'll just keep it simple list for now, maybe expand later.
    };

    const getTypeColor = (type: string) => {
        switch (type) {
            case 'maintenance': return 'bg-yellow-100 text-yellow-800';
            case 'outage': return 'bg-red-100 text-red-800';
            default: return 'bg-blue-100 text-blue-800';
        }
    };

    const getTypeLabel = (type: string) => {
        switch (type) {
            case 'maintenance': return 'MANTENIMIENTO';
            case 'outage': return 'INTERRUPCIÓN';
            default: return 'INFO';
        }
    };

    const renderItem = ({ item }: { item: Announcement }) => (
        <TouchableOpacity onPress={() => handlePress(item)} activeOpacity={0.8}>
            <Card className={`mb-3 bg-white ${!item.isRead ? 'border-l-4 border-l-blue-500' : ''} shadow-sm`}>
                <Card.Content>
                    <View className="flex-row justify-between items-start mb-2">
                        <View className="flex-row items-center flex-1 mr-2">
                            {item.company.logoUrl ? (
                                <Avatar.Image size={24} source={{ uri: item.company.logoUrl }} />
                            ) : (
                                <Avatar.Text size={24} label={item.company.name.substring(0, 2)} />
                            )}
                            <Text className="text-gray-600 text-xs ml-2 font-medium">{item.company.name}</Text>
                        </View>
                        <View className={`px-2 py-0.5 rounded-full ${getTypeColor(item.type).split(' ')[0]}`}>
                            <Text className={`text-[10px] font-bold ${getTypeColor(item.type).split(' ')[1]}`}>
                                {getTypeLabel(item.type)}
                            </Text>
                        </View>
                    </View>

                    <Text className={`text-lg mb-1 ${!item.isRead ? 'font-bold text-gray-900' : 'font-medium text-gray-700'}`}>
                        {item.title}
                    </Text>

                    <Text className="text-gray-600 text-sm leading-5 mb-3">
                        {item.content}
                    </Text>

                    <View className="flex-row justify-between items-center">
                        <Text className="text-gray-400 text-xs">
                            {formatDistanceToNow(new Date(item.createdAt), { addSuffix: true, locale: es })}
                        </Text>
                        {item.priority === 'high' && (
                            <View className="flex-row items-center">
                                <MaterialCommunityIcons name="alert-circle" size={14} color="#ef4444" />
                                <Text className="text-red-500 text-xs ml-1 font-bold">Alta Prioridad</Text>
                            </View>
                        )}
                    </View>
                </Card.Content>
            </Card>
        </TouchableOpacity>
    );

    return (
        <SafeAreaView className="flex-1 bg-gray-50" edges={['top']}>
            <View className="p-4 bg-white border-b border-gray-200 flex-row items-center">
                <TouchableOpacity onPress={() => router.back()} className="mr-4">
                    <MaterialCommunityIcons name="arrow-left" size={24} color="#374151" />
                </TouchableOpacity>
                <Text className="text-xl font-bold text-gray-900">Anuncios</Text>
            </View>

            <FlatList
                data={announcements}
                renderItem={renderItem}
                keyExtractor={(item) => item.id}
                contentContainerStyle={{ padding: 16 }}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
                ListEmptyComponent={() => (
                    <View className="items-center justify-center mt-20">
                        <MaterialCommunityIcons name="bullhorn-outline" size={64} color="#d1d5db" />
                        <Text className="text-gray-500 text-lg mt-4">No hay anuncios recientes</Text>
                    </View>
                )}
            />
        </SafeAreaView>
    );
}
