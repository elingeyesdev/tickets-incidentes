import { View, FlatList, Text, RefreshControl } from 'react-native';
import { Searchbar, Chip, ActivityIndicator } from 'react-native-paper';
import { useCompanyStore } from '@/stores/companyStore';
import { useEffect, useState, useCallback } from 'react';
import { CompanyCard } from '@/components/companies/CompanyCard';
import { debounce } from 'lodash';
import { SafeAreaView } from 'react-native-safe-area-context';

export default function ExploreCompaniesScreen() {
    const { companies, industries, fetchCompanies, fetchIndustries, isLoading } = useCompanyStore();
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedIndustry, setSelectedIndustry] = useState<string | null>(null);
    const [filterMode, setFilterMode] = useState<'all' | 'following'>('all');
    const [refreshing, setRefreshing] = useState(false);

    const loadData = async () => {
        try {
            await Promise.all([
                fetchCompanies({
                    search: searchQuery,
                    industry_id: selectedIndustry || undefined,
                    followed_by_me: filterMode === 'following' ? true : undefined
                }),
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
            fetchCompanies({
                search: query,
                industry_id: selectedIndustry || undefined,
                followed_by_me: filterMode === 'following' ? true : undefined
            });
        }, 500),
        [selectedIndustry, filterMode]
    );

    useEffect(() => {
        loadData();
    }, [filterMode, selectedIndustry]);

    const onChangeSearch = (query: string) => {
        setSearchQuery(query);
        debouncedSearch(query);
    };

    const onRefresh = () => {
        setRefreshing(true);
        loadData();
    };

    return (
        <SafeAreaView className="flex-1 bg-gray-50" edges={['top']}>
            <View className="bg-white p-4 pb-2 border-b border-gray-200">
                <Searchbar
                    placeholder="Buscar empresas..."
                    onChangeText={onChangeSearch}
                    value={searchQuery}
                    className="bg-gray-100 rounded-xl mb-4 elevation-0"
                    inputStyle={{ fontSize: 16 }}
                />

                <View className="flex-row">
                    <FlatList
                        horizontal
                        showsHorizontalScrollIndicator={false}
                        data={[
                            { id: 'all', label: 'Todas' },
                            { id: 'following', label: 'Siguiendo' },
                            ...industries.map(i => ({ id: i.id, label: i.name }))
                        ]}
                        keyExtractor={(item) => item.id}
                        renderItem={({ item }) => {
                            const isSelected =
                                (item.id === 'all' && filterMode === 'all' && !selectedIndustry) ||
                                (item.id === 'following' && filterMode === 'following') ||
                                (item.id === selectedIndustry);

                            return (
                                <Chip
                                    selected={isSelected}
                                    onPress={() => {
                                        if (item.id === 'all') {
                                            setFilterMode('all');
                                            setSelectedIndustry(null);
                                        } else if (item.id === 'following') {
                                            setFilterMode('following');
                                            setSelectedIndustry(null);
                                        } else {
                                            setFilterMode('all');
                                            setSelectedIndustry(item.id);
                                        }
                                    }}
                                    className="mr-2"
                                    showSelectedOverlay
                                >
                                    {item.label}
                                </Chip>
                            );
                        }}
                    />
                </View>
            </View>

            {isLoading && !refreshing ? (
                <View className="flex-1 justify-center items-center">
                    <ActivityIndicator size="large" color="#2563eb" />
                </View>
            ) : (
                <FlatList
                    data={companies}
                    renderItem={({ item }) => <CompanyCard company={item} />}
                    keyExtractor={(item) => item.id}
                    contentContainerStyle={{ padding: 16 }}
                    refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
                    ListEmptyComponent={() => (
                        <View className="items-center justify-center mt-20">
                            <Text className="text-gray-500 text-lg">No se encontraron empresas</Text>
                        </View>
                    )}
                />
            )}
        </SafeAreaView>
    );
}
