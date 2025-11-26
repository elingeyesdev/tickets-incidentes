import { View, Text, TouchableOpacity } from 'react-native';
import { Avatar, Chip, Button } from 'react-native-paper';
import { Company } from '../../types/company';
import { useRouter } from 'expo-router';

interface CompanyCardProps {
    company: Company;
    onFollow?: () => void;
    onUnfollow?: () => void;
}

export function CompanyCard({ company, onFollow, onUnfollow }: CompanyCardProps) {
    const router = useRouter();

    return (
        <TouchableOpacity
            className="bg-white p-4 rounded-xl mb-3 shadow-sm border border-gray-100"
            onPress={() => router.push(`/(tabs)/companies/${company.id}`)}
        >
            <View className="flex-row items-center">
                {company.logoUrl ? (
                    <Avatar.Image size={50} source={{ uri: company.logoUrl }} />
                ) : (
                    <Avatar.Text
                        size={50}
                        label={company.name.substring(0, 2).toUpperCase()}
                        style={{ backgroundColor: company.primaryColor || '#007bff' }}
                    />
                )}

                <View className="flex-1 ml-3">
                    <Text className="font-bold text-lg text-gray-900">{company.name}</Text>
                    {company.industry && (
                        <Text className="text-gray-500 text-xs">{company.industry.name}</Text>
                    )}
                </View>

                {company.isFollowing ? (
                    <Chip
                        icon="check"
                        className="bg-green-100"
                        textStyle={{ color: '#166534', fontSize: 10 }}
                    >
                        Siguiendo
                    </Chip>
                ) : null}
            </View>

            {company.description && (
                <Text className="text-gray-600 mt-3 text-sm" numberOfLines={2}>
                    {company.description}
                </Text>
            )}

            {company.isFollowing && company.statistics && (
                <View className="mt-3 flex-row items-center bg-gray-50 p-2 rounded-lg">
                    <Text className="text-xs text-gray-500">
                        {company.statistics.myTicketsCount} tickets creados
                    </Text>
                </View>
            )}
        </TouchableOpacity>
    );
}
