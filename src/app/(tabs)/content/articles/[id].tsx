import { View, ScrollView, Text, Alert, TouchableOpacity, Share, useWindowDimensions } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { Avatar, Button, Chip, Divider, ActivityIndicator } from 'react-native-paper';
import { useContentStore } from '@/stores/contentStore';
import { useEffect, useState } from 'react';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { SafeAreaView } from 'react-native-safe-area-context';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import RenderHtml from 'react-native-render-html';

export default function ArticleDetailScreen() {
    const { id } = useLocalSearchParams();
    const router = useRouter();
    const { fetchArticle, currentArticle, rateArticle, isLoading } = useContentStore();
    const { width } = useWindowDimensions();
    const [rated, setRated] = useState<boolean | null>(null);

    useEffect(() => {
        const load = async () => {
            if (typeof id === 'string') {
                try {
                    await fetchArticle(id);
                } catch (error) {
                    Alert.alert('Error', 'No se pudo cargar el artículo');
                    router.back();
                }
            }
        };
        load();
    }, [id]);

    const handleShare = async () => {
        if (!currentArticle) return;
        try {
            await Share.share({
                message: `${currentArticle.title} - ${currentArticle.excerpt}`,
            });
        } catch (error) {
            console.error(error);
        }
    };

    const handleRate = async (helpful: boolean) => {
        if (!currentArticle || rated !== null) return;
        try {
            await rateArticle(currentArticle.id, helpful);
            setRated(helpful);
            Alert.alert('Gracias', 'Tu opinión nos ayuda a mejorar.');
        } catch (error) {
            Alert.alert('Error', 'No se pudo registrar tu voto');
        }
    };

    if (isLoading || !currentArticle) {
        return (
            <View className="flex-1 justify-center items-center bg-white">
                <ActivityIndicator size="large" color="#2563eb" />
            </View>
        );
    }

    return (
        <SafeAreaView className="flex-1 bg-white" edges={['top']}>
            <ScrollView>
                {/* Header */}
                <View className="p-4">
                    <View className="flex-row justify-between items-start mb-4">
                        <TouchableOpacity onPress={() => router.back()}>
                            <MaterialCommunityIcons name="arrow-left" size={24} color="#374151" />
                        </TouchableOpacity>
                        <TouchableOpacity onPress={handleShare}>
                            <MaterialCommunityIcons name="share-variant" size={24} color="#374151" />
                        </TouchableOpacity>
                    </View>

                    <View className="flex-row items-center mb-2">
                        <Chip className="bg-blue-50 mr-2" textStyle={{ color: '#2563eb', fontSize: 10, height: 12 }} style={{ height: 24 }}>
                            {currentArticle.category.name}
                        </Chip>
                        <Text className="text-gray-400 text-xs">
                            {format(new Date(currentArticle.updatedAt), "d MMM yyyy", { locale: es })}
                        </Text>
                    </View>

                    <Text className="text-2xl font-bold text-gray-900 mb-2 leading-tight">
                        {currentArticle.title}
                    </Text>

                    <View className="flex-row items-center mt-2">
                        <Text className="text-gray-500 text-sm">Por: </Text>
                        <Text className="text-gray-700 font-medium text-sm">{currentArticle.author.displayName}</Text>
                        <Text className="text-gray-400 text-xs ml-2">• {currentArticle.company.name}</Text>
                    </View>
                </View>

                <Divider />

                {/* Content */}
                <View className="p-4">
                    <RenderHtml
                        contentWidth={width - 32}
                        source={{ html: currentArticle.content }}
                        baseStyle={{ fontSize: 16, color: '#374151', lineHeight: 24 }}
                        tagsStyles={{
                            p: { marginBottom: 10 },
                            h1: { fontSize: 22, fontWeight: 'bold', marginTop: 10, marginBottom: 10 },
                            h2: { fontSize: 20, fontWeight: 'bold', marginTop: 10, marginBottom: 10 },
                            li: { marginBottom: 5 },
                        }}
                    />
                </View>

                <Divider className="my-4" />

                {/* Feedback */}
                <View className="p-6 bg-gray-50 items-center mb-8">
                    <Text className="text-gray-700 font-bold mb-4">¿Te resultó útil este artículo?</Text>

                    <View className="flex-row">
                        <Button
                            mode={rated === true ? "contained" : "outlined"}
                            onPress={() => handleRate(true)}
                            icon="thumb-up"
                            className={`mr-4 ${rated === true ? 'bg-green-600' : 'border-gray-300'}`}
                            textColor={rated === true ? 'white' : '#4b5563'}
                            disabled={rated !== null}
                        >
                            Sí
                        </Button>
                        <Button
                            mode={rated === false ? "contained" : "outlined"}
                            onPress={() => handleRate(false)}
                            icon="thumb-down"
                            className={`${rated === false ? 'bg-red-600' : 'border-gray-300'}`}
                            textColor={rated === false ? 'white' : '#4b5563'}
                            disabled={rated !== null}
                        >
                            No
                        </Button>
                    </View>
                </View>
            </ScrollView>
        </SafeAreaView>
    );
}
