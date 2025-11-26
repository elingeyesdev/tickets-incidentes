import { View, FlatList, Text, RefreshControl } from 'react-native';
import { Searchbar, Chip, Avatar } from 'react-native-paper';
import { useCompanyStore } from '@/stores/companyStore';
import { useEffect, useState, useCallback } from 'react';
import { CompanyCard } from '@/components/companies/CompanyCard';
import { debounce } from 'lodash';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useAuthStore } from '@/stores/authStore';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { CompanyCardSkeleton } from '@/components/Skeleton';

export default function ExploreCompaniesScreen() {
    const { companies, industries, fetchCompanies, fetchIndustries, companiesLoading, setFilter, filters, clearFilters } = useCompanyStore();
    const { user } = useAuthStore();
    const [refreshing, setRefreshing] = useState(false);

    // Initial load
    useEffect(() => {
        loadData();
    }, []);

    // Fetch when filters change
    useEffect(() => {
        fetchCompanies(1);
    }, [filters.industryId, filters.followedByMe, filters.search]);

    const loadData = async () => {
        try {
            await Promise.all([
                fetchCompanies(1),
                fetchIndustries()
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
            setFilter('search', query);
        }, 500),
        []
    );

    const onChangeSearch = (query: string) => {
        debouncedSearch(query);
    };

    const onRefresh = () => {
        setRefreshing(true);
        loadData();
    };

    const renderHeader = () => (
        <View className="bg-white px-4 pt-2 pb-4 border-b border-gray-100">
            {/* Header Global */}
            <View className="flex-row justify-between items-center mb-6">
                <Text className="text-xl font-bold text-gray-900">Helpdesk</Text>
                <View className="flex-row items-center gap-3">
                    <MaterialCommunityIcons name="bell-outline" size={24} color="#374151" />
                    {user?.avatarUrl ? (
                        <Avatar.Image size={32} source={{ uri: user.avatarUrl }} />
                    ) : (
                        <Avatar.Text size={32} label={user?.displayName?.substring(0, 2) || 'US'} />
                    )}
                </View>
            </View>

            {/* Title & Subtitle */}
            <View className="mb-6">
                <Text className="text-2xl font-bold text-gray-900 mb-1">Explorar Empresas</Text>
                <Text className="text-gray-500">Encuentra empresas para obtener soporte</Text>
            </View>

            {/* Search */}
            <Searchbar
                placeholder="Buscar empresas..."
                onChangeText={onChangeSearch}
                value={undefined}
                className="bg-gray-100 rounded-xl mb-4 elevation-0"
                inputStyle={{ fontSize: 16 }}
            />

            {/* Filters */}
            <FlatList
                horizontal
                showsHorizontalScrollIndicator={false}
                data={[
                    { id: 'all', label: 'Todas' },
                    { id: 'following', label: 'Siguiendo' },
                    ...industries.map(i => ({ id: i.id, label: i.name }))
                ]}
                keyExtractor={(item) => item.id}
                contentContainerStyle={{ paddingRight: 16 }}
                renderItem={({ item }) => {
                    const isSelected =
                        (item.id === 'all' && !filters.followedByMe && !filters.industryId) ||
                        (item.id === 'following' && filters.followedByMe) ||
                        (item.id === filters.industryId);

                    return (
                        <Chip
                            selected={isSelected}
                            onPress={() => {
                                if (item.id === 'all') {
                                    clearFilters();
                                } else if (item.id === 'following') {
                                    setFilter('followedByMe', true);
                                    setFilter('industryId', null);
                                } else {
                                    setFilter('followedByMe', false);
                                    setFilter('industryId', item.id);
                                }
                            }}
                            className={`mr-2 ${isSelected ? 'bg-[#7C3AED]' : 'bg-gray-100'}`}
                            textStyle={{ color: isSelected ? 'white' : '#374151', fontWeight: isSelected ? 'bold' : 'normal' }}
                            showSelectedOverlay={true}
                        >
                            {item.label}
                        </Chip>
                    );
                }}
            />
        </View>
    );

    return (
        <SafeAreaView className="flex-1 bg-gray-50" edges={['top']}>
            <FlatList
                data={companies}
                renderItem={({ item }) => <CompanyCard company={item} />}
                keyExtractor={(item) => item.id}
                contentContainerStyle={{ padding: 16, paddingBottom: 80 }}
                ListHeaderComponent={renderHeader}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
                ListEmptyComponent={() => (
                    companiesLoading && !refreshing ? (
                        <View>
                            {Array.from({ length: 6 }).map((_, i) => (
                                <CompanyCardSkeleton key={i} />
                            ))}
                        </View>
                    ) : (
                        <View className="items-center justify-center mt-20">
                            <MaterialCommunityIcons name="domain-off" size={48} color="#9CA3AF" />
                            <Text className="text-gray-500 text-lg mt-4">No se encontraron empresas</Text>
                        </View>
                    )
                )}
                onEndReached={() => {
                    // Implement pagination load more if needed
                }}
            />
        </SafeAreaView>
    );
}
