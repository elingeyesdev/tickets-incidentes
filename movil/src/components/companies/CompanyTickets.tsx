import React from 'react';
import { View, Text, TouchableOpacity } from 'react-native';
import { useRouter } from 'expo-router';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { CompanyDetail } from '@/types/company';

interface CompanyTicketsProps {
    company: CompanyDetail;
}

export function CompanyTickets({ company }: CompanyTicketsProps) {
    const router = useRouter();

    if (!company.isFollowedByMe) return null;

    return (
        <View className="p-6 bg-gray-50 mt-2 border-t border-b border-gray-100">
            <View className="flex-row justify-between items-center mb-4">
                <View className="flex-row items-center">
                    <MaterialCommunityIcons name="ticket-confirmation-outline" size={24} color="#2563eb" />
                    <Text className="text-lg font-bold text-gray-900 ml-2">Mis Tickets</Text>
                </View>
            </View>

            {/* Stats Card */}
            <View className="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex-row items-center justify-between mb-4">
                <View>
                    <Text className="text-3xl font-bold text-blue-600">
                        {company.myTicketsCount || 0}
                    </Text>
                    <Text className="text-gray-500 text-sm">Tickets totales</Text>
                </View>

                {company.lastTicketCreatedAt && (
                    <View className="items-end">
                        <Text className="text-xs text-gray-400 mb-1">Último ticket</Text>
                        <Text className="text-gray-700 font-medium">
                            {new Date(company.lastTicketCreatedAt).toLocaleDateString()}
                        </Text>
                    </View>
                )}
            </View>

            {/* Actions */}
            <TouchableOpacity
                className="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex-row items-center justify-between active:bg-gray-50"
                onPress={() => router.push('/(tabs)/tickets')}
            >
                <Text className="text-gray-700 font-medium">Ver todos mis tickets</Text>
                <MaterialCommunityIcons name="chevron-right" size={20} color="#9CA3AF" />
            </TouchableOpacity>
        </View>
    );
}
