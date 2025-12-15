import React from 'react';
import { View, Text } from 'react-native';
import { Avatar, Chip } from 'react-native-paper';
import { CompanyDetail } from '@/types/company';
import { MaterialCommunityIcons } from '@expo/vector-icons';

interface CompanyHeaderProps {
    company: CompanyDetail;
}

export function CompanyHeader({ company }: CompanyHeaderProps) {
    return (
        <View>
            {/* Hero Background */}
            <View
                className="items-center pt-8 pb-12 px-4"
                style={{ backgroundColor: company.primaryColor || '#007bff' }}
            >
                {/* Avatar */}
                <View className="bg-white p-1 rounded-2xl shadow-lg mb-4">
                    {company.logoUrl ? (
                        <Avatar.Image
                            size={80}
                            source={{ uri: company.logoUrl }}
                            style={{ borderRadius: 14, backgroundColor: 'white' }}
                        />
                    ) : (
                        <View
                            style={{
                                width: 80,
                                height: 80,
                                borderRadius: 14,
                                backgroundColor: company.primaryColor || '#007bff',
                                justifyContent: 'center',
                                alignItems: 'center'
                            }}
                        >
                            <Text className="text-white font-bold text-3xl">
                                {company.name.substring(0, 2).toUpperCase()}
                            </Text>
                        </View>
                    )}
                </View>

                {/* Name */}
                <Text className="text-2xl font-bold text-white text-center mb-2 shadow-sm">
                    {company.name}
                </Text>

                {/* Industry Badge */}
                <View className="bg-white/20 px-3 py-1 rounded-full backdrop-blur-sm">
                    <Text className="text-white font-medium text-sm">
                        {company.industry.name}
                    </Text>
                </View>
            </View>

            {/* Quick Info Bar */}
            <View className="flex-row justify-center items-center py-4 bg-white border-b border-gray-100 -mt-4 rounded-t-3xl shadow-sm mx-4">
                <View className="flex-row items-center mr-6">
                    <MaterialCommunityIcons name="map-marker" size={18} color="#6B7280" />
                    <Text className="text-gray-600 ml-1 font-medium">
                        {company.city}, {company.country}
                    </Text>
                </View>
                <View className="h-4 w-[1px] bg-gray-300 mr-6" />
                <View className="flex-row items-center">
                    <MaterialCommunityIcons name="account-group" size={18} color="#6B7280" />
                    <Text className="text-gray-600 ml-1 font-medium">
                        {company.followersCount} seguidores
                    </Text>
                </View>
            </View>
        </View>
    );
}
