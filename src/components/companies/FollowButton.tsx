import React, { useState } from 'react';
import { Button, Text } from 'react-native-paper';
import { View, Alert } from 'react-native';
import { useCompanyStore } from '@/stores/companyStore';

interface FollowButtonProps {
    companyId: string;
    isFollowing: boolean;
    companyName: string;
    onFollowChange?: (isFollowing: boolean) => void;
}

export function FollowButton({ companyId, isFollowing, companyName, onFollowChange }: FollowButtonProps) {
    const { followCompany, unfollowCompany } = useCompanyStore();
    const [loading, setLoading] = useState(false);

    const handleFollow = async () => {
        setLoading(true);
        try {
            await followCompany(companyId);
            onFollowChange?.(true);
        } catch (error) {
            Alert.alert('Error', 'No se pudo seguir a la empresa');
        } finally {
            setLoading(false);
        }
    };

    const handleUnfollow = () => {
        Alert.alert(
            `¿Dejar de seguir a ${companyName}?`,
            "No podrás crear tickets ni ver sus anuncios.",
            [
                { text: "Cancelar", style: "cancel" },
                {
                    text: "Dejar de seguir",
                    style: "destructive",
                    onPress: async () => {
                        setLoading(true);
                        try {
                            await unfollowCompany(companyId);
                            onFollowChange?.(false);
                        } catch (error) {
                            Alert.alert('Error', 'No se pudo dejar de seguir');
                        } finally {
                            setLoading(false);
                        }
                    }
                }
            ]
        );
    };

    if (isFollowing) {
        return (
            <View className="flex-row gap-2">
                <Button
                    mode="outlined"
                    icon="check"
                    className="flex-1 border-green-600 bg-green-50"
                    textColor="#166534"
                    compact
                >
                    Siguiendo
                </Button>
                <Button
                    mode="outlined"
                    onPress={handleUnfollow}
                    loading={loading}
                    className="flex-1 border-red-200"
                    textColor="#ef4444"
                    compact
                >
                    Dejar de seguir
                </Button>
            </View>
        );
    }

    return (
        <Button
            mode="contained"
            icon="plus"
            onPress={handleFollow}
            loading={loading}
            className="bg-[#7C3AED]"
            contentStyle={{ height: 48 }}
        >
            Seguir empresa
        </Button>
    );
}
