import { View, ScrollView, Text, Alert, Linking, TouchableOpacity } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { Avatar, Button, Chip, Divider, ActivityIndicator } from 'react-native-paper';
import { useCompanyStore } from '@/stores/companyStore';
import { useEffect, useState } from 'react';
import { Company } from '@/types/company';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { SafeAreaView } from 'react-native-safe-area-context';

export default function CompanyDetailScreen() {
    const { id } = useLocalSearchParams();
    const router = useRouter();
    const { fetchCompany, followCompany, unfollowCompany } = useCompanyStore();
    const [company, setCompany] = useState<Company | null>(null);
    const [loading, setLoading] = useState(true);
    const [followLoading, setFollowLoading] = useState(false);

    useEffect(() => {
        const load = async () => {
            try {
                if (typeof id === 'string') {
                    const data = await fetchCompany(id);
                    setCompany(data);
                }
            } catch (error) {
                Alert.alert('Error', 'No se pudo cargar la información de la empresa');
                router.back();
            } finally {
                setLoading(false);
            }
        };
        load();
    }, [id]);

    const handleFollowToggle = async () => {
        if (!company) return;
        setFollowLoading(true);
        try {
            if (company.isFollowing) {
                await unfollowCompany(company.id);
                setCompany({ ...company, isFollowing: false });
            } else {
                await followCompany(company.id);
                setCompany({ ...company, isFollowing: true });
            }
        } catch (error) {
            Alert.alert('Error', 'No se pudo actualizar el seguimiento');
        } finally {
            setFollowLoading(false);
        }
    };

    if (loading || !company) {
        return (
            <View className="flex-1 justify-center items-center bg-white">
                <ActivityIndicator size="large" color="#2563eb" />
            </View>
        );
    }

    return (
        <SafeAreaView className="flex-1 bg-white" edges={['top']}>
            <ScrollView>
                {/* Header Hero */}
                <View
                    className="items-center p-6 pb-8"
                    style={{ backgroundColor: company.primaryColor || '#f3f4f6' }}
                >
                    <View className="bg-white p-1 rounded-full shadow-lg mb-4">
                        {company.logoUrl ? (
                            <Avatar.Image size={80} source={{ uri: company.logoUrl }} />
                        ) : (
                            <Avatar.Text size={80} label={company.name.substring(0, 2).toUpperCase()} />
                        )}
                    </View>
                    <Text className="text-2xl font-bold text-white text-center mb-1 shadow-sm">
                        {company.name}
                    </Text>
                    {company.industry && (
                        <Chip className="bg-white/90 mt-2" textStyle={{ color: '#374151' }}>
                            {company.industry.name}
                        </Chip>
                    )}
                </View>

                {/* Actions */}
                <View className="flex-row p-4 -mt-6 bg-white rounded-t-3xl shadow-sm justify-around">
                    <Button
                        mode={company.isFollowing ? "outlined" : "contained"}
                        onPress={handleFollowToggle}
                        loading={followLoading}
                        icon={company.isFollowing ? "check" : "plus"}
                        className={`flex-1 mr-2 ${company.isFollowing ? 'border-green-600' : 'bg-blue-600'}`}
                        textColor={company.isFollowing ? '#166534' : 'white'}
                    >
                        {company.isFollowing ? 'Siguiendo' : 'Seguir'}
                    </Button>

                    {company.isFollowing && (
                        <Button
                            mode="contained"
                            onPress={() => router.push({ pathname: '/(tabs)/tickets/create', params: { companyId: company.id } })}
                            icon="ticket-plus"
                            className="flex-1 ml-2 bg-blue-600"
                        >
                            Crear Ticket
                        </Button>
                    )}
                </View>

                {/* Contact Info */}
                <View className="p-6">
                    <Text className="text-lg font-bold text-gray-900 mb-4">Contacto</Text>

                    <TouchableOpacity
                        className="flex-row items-center mb-4"
                        onPress={() => Linking.openURL(`mailto:${company.supportEmail}`)}
                    >
                        <View className="bg-blue-50 p-2 rounded-full mr-3">
                            <MaterialCommunityIcons name="email" size={20} color="#2563eb" />
                        </View>
                        <Text className="text-gray-700">{company.supportEmail}</Text>
                    </TouchableOpacity>

                    {company.phone && (
                        <TouchableOpacity
                            className="flex-row items-center mb-4"
                            onPress={() => Linking.openURL(`tel:${company.phone}`)}
                        >
                            <View className="bg-green-50 p-2 rounded-full mr-3">
                                <MaterialCommunityIcons name="phone" size={20} color="#166534" />
                            </View>
                            <Text className="text-gray-700">{company.phone}</Text>
                        </TouchableOpacity>
                    )}

                    {company.website && (
                        <TouchableOpacity
                            className="flex-row items-center mb-4"
                            onPress={() => Linking.openURL(company.website!)}
                        >
                            <View className="bg-purple-50 p-2 rounded-full mr-3">
                                <MaterialCommunityIcons name="web" size={20} color="#9333ea" />
                            </View>
                            <Text className="text-gray-700">{company.website}</Text>
                        </TouchableOpacity>
                    )}

                    {company.description && (
                        <View className="mt-4">
                            <Text className="text-lg font-bold text-gray-900 mb-2">Acerca de</Text>
                            <Text className="text-gray-600 leading-relaxed">
                                {company.description}
                            </Text>
                        </View>
                    )}
                </View>

                <Divider />

                {/* Business Hours */}
                <View className="p-6">
                    <Text className="text-lg font-bold text-gray-900 mb-4">Horarios de Atención</Text>
                    {Object.entries(company.businessHours || {}).map(([day, hours]) => (
                        <View key={day} className="flex-row justify-between mb-2">
                            <Text className="text-gray-600 capitalize">{day}</Text>
                            <Text className="text-gray-900 font-medium">
                                {hours.open} - {hours.close}
                            </Text>
                        </View>
                    ))}
                    <Text className="text-xs text-gray-400 mt-2">
                        Zona horaria: {company.timezone}
                    </Text>
                </View>

                {/* My Tickets Preview */}
                {company.isFollowing && company.statistics && (
                    <View className="p-6 bg-gray-50">
                        <View className="flex-row justify-between items-center mb-4">
                            <Text className="text-lg font-bold text-gray-900">Mis Tickets</Text>
                            <TouchableOpacity onPress={() => router.push('/(tabs)/tickets')}>
                                <Text className="text-blue-600 font-medium">Ver todos</Text>
                            </TouchableOpacity>
                        </View>

                        <View className="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                            <Text className="text-3xl font-bold text-blue-600 mb-1">
                                {company.statistics.myTicketsCount}
                            </Text>
                            <Text className="text-gray-500">Tickets totales en esta empresa</Text>
                        </View>
                    </View>
                )}
            </ScrollView>
        </SafeAreaView>
    );
}
