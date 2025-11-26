import { View, FlatList, Text, RefreshControl, TouchableOpacity, Modal, ScrollView } from 'react-native';
import { Searchbar, Chip, Button, Divider, IconButton } from 'react-native-paper';
import { useCompanyStore } from '@/stores/companyStore';
import { useEffect, useState, useCallback } from 'react';
import { CompanyCard } from '@/components/companies/CompanyCard';
import { debounce } from 'lodash';
import { SafeAreaView } from 'react-native-safe-area-context';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { CompanyCardSkeleton } from '@/components/Skeleton';

export default function ExploreCompaniesScreen() {
    const { companies, industries, fetchCompanies, fetchIndustries, companiesLoading, setFilter, filters, clearFilters } = useCompanyStore();
    const [refreshing, setRefreshing] = useState(false);
    const [showIndustryModal, setShowIndustryModal] = useState(false);

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
        <View className="bg-white px-4 pb-4 border-b border-gray-100">
            {/* Title & Subtitle */}
            <View className="mb-4">
                <Text className="text-2xl font-bold text-gray-900 mb-1">Explorar Empresas</Text>
                <Text className="text-gray-500">Encuentra empresas para obtener soporte</Text>
            </View>

            {/* Search */}
            <Searchbar
                placeholder="Buscar empresas..."
                onChangeText={onChangeSearch}
                value={undefined}
                className="bg-gray-50 border border-gray-200 rounded-xl mb-4 elevation-0"
                inputStyle={{ fontSize: 16, color: '#1f2937' }}
                iconColor="#6b7280"
                placeholderTextColor="#9ca3af"
            />

            {/* Filters Row */}
            <View className="flex-row items-center">
                {/* Filter Button */}
                <TouchableOpacity
                    onPress={() => setShowIndustryModal(true)}
                    className="bg-gray-100 p-2 rounded-full mr-3 border border-gray-200"
                >
                    <MaterialCommunityIcons name="filter-variant" size={20} color="#374151" />
                </TouchableOpacity>

                {/* Chips List */}
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
                                className={`mr-2 border ${isSelected ? 'bg-[#7C3AED] border-[#7C3AED]' : 'bg-white border-gray-200'}`}
                                textStyle={{ color: isSelected ? 'white' : '#374151', fontWeight: isSelected ? 'bold' : 'normal' }}
                                showSelectedOverlay={true}
                            >
                                {item.label}
                            </Chip>
                        );
                    }}
                />
            </View>
        </View>
    );

    return (
        <SafeAreaView className="flex-1 bg-gray-50" edges={['top']}>
            <FlatList
                data={companies}
                renderItem={({ item }) => <CompanyCard company={item} />}
                keyExtractor={(item) => item.id}
                contentContainerStyle={{ paddingBottom: 80 }}
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
            />

            {/* Industry Filter Modal */}
            <Modal
                visible={showIndustryModal}
                animationType="slide"
                transparent={true}
                onRequestClose={() => setShowIndustryModal(false)}
            >
                <View className="flex-1 justify-end bg-black/50">
                    <View className="bg-white rounded-t-3xl h-[70%]">
                        <View className="p-4 border-b border-gray-100 flex-row justify-between items-center">
                            <Text className="text-xl font-bold text-gray-900">Filtrar por Industria</Text>
                            <IconButton icon="close" onPress={() => setShowIndustryModal(false)} />
                        </View>

                        <ScrollView className="flex-1 p-4">
                            <TouchableOpacity
                                className={`p-4 rounded-xl mb-2 flex-row justify-between items-center ${!filters.industryId ? 'bg-blue-50 border border-blue-200' : 'bg-gray-50 border border-gray-100'}`}
                                onPress={() => {
                                    setFilter('industryId', null);
                                    setShowIndustryModal(false);
                                }}
                            >
                                <Text className={`font-medium ${!filters.industryId ? 'text-blue-700' : 'text-gray-700'}`}>Todas las industrias</Text>
                                {!filters.industryId && <MaterialCommunityIcons name="check" size={20} color="#2563eb" />}
                            </TouchableOpacity>

                            {industries.map((industry) => (
                                <TouchableOpacity
                                    key={industry.id}
                                    className={`p-4 rounded-xl mb-2 flex-row justify-between items-center ${filters.industryId === industry.id ? 'bg-blue-50 border border-blue-200' : 'bg-gray-50 border border-gray-100'}`}
                                    onPress={() => {
                                        setFilter('industryId', industry.id);
                                        setFilter('followedByMe', false); // Switch to all mode with filter
                                        setShowIndustryModal(false);
                                    }}
                                >
                                    <Text className={`font-medium ${filters.industryId === industry.id ? 'text-blue-700' : 'text-gray-700'}`}>
                                        {industry.name}
                                    </Text>
                                    {filters.industryId === industry.id && <MaterialCommunityIcons name="check" size={20} color="#2563eb" />}
                                </TouchableOpacity>
                            ))}
                        </ScrollView>
                    </View>
                </View>
            </Modal>
        </SafeAreaView>
    );
}
