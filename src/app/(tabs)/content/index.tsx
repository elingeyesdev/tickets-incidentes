import { View, FlatList, Text, RefreshControl, TouchableOpacity } from 'react-native';
import { Searchbar, Chip, Card, Avatar, ActivityIndicator } from 'react-native-paper';
import { useContentStore } from '@/stores/contentStore';
import { useCompanyStore } from '@/stores/companyStore';
import { useEffect, useState, useCallback } from 'react';
import { Article } from '@/types/content';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useRouter } from 'expo-router';
import { debounce } from 'lodash';

export default function HelpCenterScreen() {
    const router = useRouter();
    const { articles, fetchArticles, isLoading } = useContentStore();
    const { followedCompanies, fetchFollowedCompanies } = useCompanyStore();

    const [searchQuery, setSearchQuery] = useState('');
    const [selectedCompanyId, setSelectedCompanyId] = useState<string | null>(null);
    const [refreshing, setRefreshing] = useState(false);

    const loadData = async () => {
        try {
            await Promise.all([
                fetchArticles({
                    search: searchQuery,
                    company_id: selectedCompanyId || undefined
                }),
                fetchFollowedCompanies()
            ]);
        } catch (error) {
            console.error(error);
        } finally {
            setRefreshing(false);
        }
    };

    // Debounced search
    const debouncedSearch = useCallback(
        debounce((query) => {
            fetchArticles({
                search: query,
                company_id: selectedCompanyId || undefined
            });
        }, 500),
        [selectedCompanyId]
    );

    useEffect(() => {
        loadData();
    }, [selectedCompanyId]);

    const onChangeSearch = (query: string) => {
        setSearchQuery(query);
        debouncedSearch(query);
    };

    const onRefresh = () => {
        setRefreshing(true);
        loadData();
    };

    const renderItem = ({ item }: { item: Article }) => (
        <TouchableOpacity
            onPress={() => router.push(`/(tabs)/content/articles/${item.id}`)}
            activeOpacity={0.8}
        >
            <Card className="mb-3 bg-white shadow-sm">
                <Card.Content>
                    <Text className="font-bold text-lg text-gray-900 mb-1">{item.title}</Text>
                    <Text className="text-gray-600 text-sm mb-2" numberOfLines={2}>{item.excerpt}</Text>

                    <View className="flex-row justify-between items-center mt-2">
                        <View className="flex-row items-center">
                            {item.company.name && (
                                <Chip icon="domain" textStyle={{ fontSize: 10, height: 12 }} style={{ height: 24 }} className="mr-2 bg-gray-100">
                                    {item.company.name}
                                </Chip>
                            )}
                            <Text className="text-gray-400 text-xs">{item.category.name}</Text>
                        </View>

                        <View className="flex-row items-center">
                            <MaterialCommunityIcons name="thumb-up-outline" size={14} color="#9ca3af" />
                            <Text className="text-gray-400 text-xs ml-1">{item.helpfulCount}</Text>
                        </View>
                    </View>
                </Card.Content>
            </Card>
        </TouchableOpacity>
    );

    return (
        <SafeAreaView className="flex-1 bg-gray-50" edges={['top']}>
            <View className="bg-white p-4 pb-2 border-b border-gray-200">
                <Text className="text-2xl font-bold text-gray-900 mb-4">Centro de Ayuda</Text>

                {/* Announcements Banner */}
                <TouchableOpacity
                    className="bg-blue-50 p-3 rounded-xl mb-4 flex-row items-center justify-between"
                    onPress={() => router.push('/(tabs)/content/announcements')}
                >
                    <View className="flex-row items-center">
                        <View className="bg-blue-100 p-2 rounded-full mr-3">
                            <MaterialCommunityIcons name="bullhorn" size={20} color="#2563eb" />
                        </View>
                        <View>
                            <Text className="font-bold text-blue-900">Anuncios y Novedades</Text>
                            <Text className="text-blue-700 text-xs">Mantente informado sobre actualizaciones</Text>
                        </View>
                    </View>
                    <MaterialCommunityIcons name="chevron-right" size={24} color="#2563eb" />
                </TouchableOpacity>

                <Searchbar
                    placeholder="Buscar artículos..."
                    onChangeText={onChangeSearch}
                    value={searchQuery}
                    className="bg-gray-100 rounded-xl mb-4 elevation-0"
                    inputStyle={{ fontSize: 16 }}
                />

                {/* Company Filters */}
                <View className="flex-row mb-2">
                    <FlatList
                        horizontal
                        showsHorizontalScrollIndicator={false}
                        data={[{ id: 'all', name: 'Todas' }, ...followedCompanies]}
                        keyExtractor={(item) => item.id}
                        renderItem={({ item }) => (
                            <Chip
                                selected={selectedCompanyId === (item.id === 'all' ? null : item.id)}
                                onPress={() => setSelectedCompanyId(item.id === 'all' ? null : item.id)}
                                className="mr-2"
                                showSelectedOverlay
                                avatar={item.id !== 'all' && 'logoUrl' in item && item.logoUrl ? <Avatar.Image size={24} source={{ uri: item.logoUrl }} /> : undefined}
                            >
                                {item.name}
                            </Chip>
                        )}
                    />
                </View>
            </View>

            {isLoading && !refreshing ? (
                <View className="flex-1 justify-center items-center">
                    <ActivityIndicator size="large" color="#2563eb" />
                </View>
            ) : (
                <FlatList
                    data={articles}
                    renderItem={renderItem}
                    keyExtractor={(item) => item.id}
                    contentContainerStyle={{ padding: 16 }}
                    refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
                    ListEmptyComponent={() => (
                        <View className="items-center justify-center mt-20">
                            <MaterialCommunityIcons name="book-open-page-variant-outline" size={64} color="#d1d5db" />
                            <Text className="text-gray-500 text-lg mt-4">No se encontraron artículos</Text>
                        </View>
                    )}
                />
            )}
        </SafeAreaView>
    );
}
