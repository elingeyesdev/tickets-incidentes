import React from 'react';
import { View, Text, TouchableOpacity, Linking, Alert, Clipboard } from 'react-native';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { CompanyDetail } from '@/types/company';

interface ContactSectionProps {
    company: CompanyDetail;
}

export function ContactSection({ company }: ContactSectionProps) {
    const handleEmail = () => {
        Linking.openURL(`mailto:${company.supportEmail}`);
    };

    const handlePhone = () => {
        if (company.phone) {
            Linking.openURL(`tel:${company.phone}`);
        }
    };

    const handleWebsite = () => {
        if (company.website) {
            Linking.openURL(company.website);
        }
    };

    const copyToClipboard = (text: string, label: string) => {
        Clipboard.setString(text);
        Alert.alert('Copiado', `${label} copiado al portapapeles`);
    };

    return (
        <View className="p-6 bg-white mt-2">
            <Text className="text-lg font-bold text-gray-900 mb-4">Contacto</Text>

            <TouchableOpacity
                className="flex-row items-center mb-4 active:opacity-70"
                onPress={handleEmail}
                onLongPress={() => copyToClipboard(company.supportEmail, 'Email')}
            >
                <View className="bg-blue-50 p-3 rounded-full mr-4">
                    <MaterialCommunityIcons name="email-outline" size={24} color="#2563eb" />
                </View>
                <View className="flex-1">
                    <Text className="text-gray-900 font-medium text-base">{company.supportEmail}</Text>
                    <Text className="text-gray-500 text-xs">Correo de soporte</Text>
                </View>
                <MaterialCommunityIcons name="content-copy" size={20} color="#9CA3AF" />
            </TouchableOpacity>

            {company.phone && (
                <TouchableOpacity
                    className="flex-row items-center mb-4 active:opacity-70"
                    onPress={handlePhone}
                    onLongPress={() => copyToClipboard(company.phone!, 'Teléfono')}
                >
                    <View className="bg-green-50 p-3 rounded-full mr-4">
                        <MaterialCommunityIcons name="phone-outline" size={24} color="#16A34A" />
                    </View>
                    <View className="flex-1">
                        <Text className="text-gray-900 font-medium text-base">{company.phone}</Text>
                        <Text className="text-gray-500 text-xs">Teléfono</Text>
                    </View>
                    <MaterialCommunityIcons name="phone" size={20} color="#9CA3AF" />
                </TouchableOpacity>
            )}

            {company.website && (
                <TouchableOpacity
                    className="flex-row items-center active:opacity-70"
                    onPress={handleWebsite}
                >
                    <View className="bg-purple-50 p-3 rounded-full mr-4">
                        <MaterialCommunityIcons name="web" size={24} color="#9333ea" />
                    </View>
                    <View className="flex-1">
                        <Text className="text-gray-900 font-medium text-base">{company.website}</Text>
                        <Text className="text-gray-500 text-xs">Sitio web</Text>
                    </View>
                    <MaterialCommunityIcons name="open-in-new" size={20} color="#9CA3AF" />
                </TouchableOpacity>
            )}
        </View>
    );
}
