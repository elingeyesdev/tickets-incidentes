import { View, FlatList, Text, TextInput, KeyboardAvoidingView, Platform, TouchableOpacity, Alert } from 'react-native';
import { Avatar, IconButton, ActivityIndicator } from 'react-native-paper';
import { useTicketStore } from '@/stores/ticketStore';
import { useEffect, useState, useRef } from 'react';
import { Ticket, TicketResponse } from '@/types/ticket';
import { useAuthStore } from '@/stores/authStore';
import { formatDistanceToNow } from 'date-fns';
import { es } from 'date-fns/locale';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import * as ImagePicker from 'expo-image-picker';

interface TicketConversationProps {
    ticket: Ticket;
}

export function TicketConversation({ ticket }: TicketConversationProps) {
    const { fetchTicketResponses, createResponse, currentTicketResponses, isLoading } = useTicketStore();
    const user = useAuthStore((state) => state.user);
    const [message, setMessage] = useState('');
    const [sending, setSending] = useState(false);
    const [attachments, setAttachments] = useState<ImagePicker.ImagePickerAsset[]>([]);
    const flatListRef = useRef<FlatList>(null);

    useEffect(() => {
        fetchTicketResponses(ticket.ticketCode);
        const interval = setInterval(() => {
            fetchTicketResponses(ticket.ticketCode);
        }, 10000); // Poll every 10s
        return () => clearInterval(interval);
    }, [ticket.ticketCode]);

    const handleSend = async () => {
        if (!message.trim() && attachments.length === 0) return;

        setSending(true);
        try {
            await createResponse(ticket.ticketCode, message, attachments);
            setMessage('');
            setAttachments([]);
            flatListRef.current?.scrollToEnd({ animated: true });
        } catch (error) {
            Alert.alert('Error', 'No se pudo enviar el mensaje');
        } finally {
            setSending(false);
        }
    };

    const pickImage = async () => {
        const result = await ImagePicker.launchImageLibraryAsync({
            mediaTypes: ImagePicker.MediaTypeOptions.All,
            allowsEditing: false,
            quality: 0.8,
            allowsMultipleSelection: true,
        });

        if (!result.canceled) {
            setAttachments([...attachments, ...result.assets]);
        }
    };

    const renderItem = ({ item }: { item: TicketResponse }) => {
        const isMe = item.authorType === 'user'; // Or check ID

        return (
            <View className={`flex-row mb-4 ${isMe ? 'justify-end' : 'justify-start'}`}>
                {!isMe && (
                    <Avatar.Image
                        size={32}
                        source={{ uri: item.author.avatarUrl || undefined }}
                        className="mr-2 mt-1"
                    />
                )}

                <View
                    className={`max-w-[80%] p-3 rounded-2xl ${isMe ? 'bg-blue-600 rounded-tr-none' : 'bg-gray-100 rounded-tl-none'
                        }`}
                >
                    {!isMe && (
                        <View className="flex-row items-center mb-1">
                            <Text className="text-xs font-bold text-gray-500 mr-2">
                                {item.author.displayName}
                            </Text>
                            {item.authorType === 'agent' && (
                                <View className="bg-blue-100 px-1.5 py-0.5 rounded">
                                    <Text className="text-[10px] text-blue-800 font-bold">AGENTE</Text>
                                </View>
                            )}
                        </View>
                    )}

                    <Text className={`text-base ${isMe ? 'text-white' : 'text-gray-800'}`}>
                        {item.content}
                    </Text>

                    {item.attachments && item.attachments.length > 0 && (
                        <View className="mt-2 pt-2 border-t border-white/20">
                            <Text className={`text-xs ${isMe ? 'text-blue-100' : 'text-gray-500'}`}>
                                {item.attachments.length} archivo(s) adjunto(s)
                            </Text>
                        </View>
                    )}

                    <Text className={`text-[10px] mt-1 text-right ${isMe ? 'text-blue-200' : 'text-gray-400'}`}>
                        {formatDistanceToNow(new Date(item.createdAt), { addSuffix: true, locale: es })}
                    </Text>
                </View>
            </View>
        );
    };

    return (
        <View className="flex-1 bg-white">
            <FlatList
                ref={flatListRef}
                data={currentTicketResponses}
                renderItem={renderItem}
                keyExtractor={(item) => item.id}
                contentContainerStyle={{ padding: 16, paddingBottom: 20 }}
                onContentSizeChange={() => flatListRef.current?.scrollToEnd({ animated: false })}
            />

            {ticket.status !== 'closed' && (
                <KeyboardAvoidingView
                    behavior={Platform.OS === 'ios' ? 'padding' : undefined}
                    keyboardVerticalOffset={Platform.OS === 'ios' ? 90 : 0}
                    className="border-t border-gray-200 p-2 bg-white"
                >
                    {attachments.length > 0 && (
                        <View className="flex-row p-2">
                            {attachments.map((file, index) => (
                                <View key={index} className="bg-gray-100 rounded-lg p-1 mr-2 flex-row items-center">
                                    <MaterialCommunityIcons name="file" size={14} color="#6b7280" />
                                    <Text className="text-xs text-gray-600 ml-1 max-w-[100px]" numberOfLines={1}>
                                        Archivo {index + 1}
                                    </Text>
                                    <TouchableOpacity onPress={() => setAttachments(attachments.filter((_, i) => i !== index))}>
                                        <MaterialCommunityIcons name="close-circle" size={16} color="#ef4444" />
                                    </TouchableOpacity>
                                </View>
                            ))}
                        </View>
                    )}

                    <View className="flex-row items-end">
                        <IconButton icon="paperclip" onPress={pickImage} />

                        <TextInput
                            value={message}
                            onChangeText={setMessage}
                            placeholder="Escribe una respuesta..."
                            multiline
                            className="flex-1 bg-gray-100 rounded-2xl px-4 py-2 min-h-[40px] max-h-[100px] mr-2 text-base"
                            style={{ paddingTop: 10, paddingBottom: 10 }}
                        />

                        <View className="pb-1">
                            {sending ? (
                                <ActivityIndicator size="small" color="#2563eb" className="m-2" />
                            ) : (
                                <TouchableOpacity
                                    onPress={handleSend}
                                    disabled={!message.trim() && attachments.length === 0}
                                    className={`p-2 rounded-full ${(!message.trim() && attachments.length === 0) ? 'bg-gray-200' : 'bg-blue-600'}`}
                                >
                                    <MaterialCommunityIcons name="send" size={20} color="white" />
                                </TouchableOpacity>
                            )}
                        </View>
                    </View>
                </KeyboardAvoidingView>
            )}

            {ticket.status === 'closed' && (
                <View className="p-4 bg-gray-100 items-center">
                    <Text className="text-gray-500">Este ticket está cerrado y no admite nuevas respuestas.</Text>
                </View>
            )}
        </View>
    );
}
