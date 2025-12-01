import { View, Text, TouchableOpacity } from 'react-native';
import { Avatar } from 'react-native-paper';
import { Ticket } from '@/types/ticket';
import { formatDistanceToNow } from 'date-fns';
import { es } from 'date-fns/locale';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { useDebounceNavigation } from '@/hooks/useDebounceNavigation';

interface TicketCardProps {
    ticket: Ticket;
}

export function TicketCard({ ticket }: TicketCardProps) {
    const { push } = useDebounceNavigation();

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'open': return 'bg-blue-100 text-blue-800'; // Waiting for agent
            case 'pending': return 'bg-yellow-100 text-yellow-800'; // Waiting for user
            case 'resolved': return 'bg-green-100 text-green-800';
            case 'closed': return 'bg-gray-100 text-gray-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    const getStatusLabel = (status: string) => {
        switch (status) {
            case 'open': return 'ABIERTO';
            case 'pending': return 'PENDIENTE';
            case 'resolved': return 'RESUELTO';
            case 'closed': return 'CERRADO';
            default: return status.toUpperCase();
        }
    };

    return (
        <TouchableOpacity
            className="bg-white p-4 rounded-xl mb-3 shadow-sm border border-gray-100"
            onPress={() => push(`/(tabs)/tickets/${ticket.ticketCode}`)}
        >
            <View className="flex-row justify-between items-start mb-2">
                <View className="flex-row items-center flex-1 mr-2">
                    <Text className="text-gray-500 font-mono text-xs mr-2">{ticket.ticketCode}</Text>

                    {/* Priority Badge */}
                    <View className={`px-1.5 py-0.5 rounded mr-2 ${ticket.priority === 'high' ? 'bg-red-100' :
                        ticket.priority === 'medium' ? 'bg-yellow-100' : 'bg-green-100'
                        }`}>
                        <Text className={`text-[10px] font-bold ${ticket.priority === 'high' ? 'text-red-800' :
                            ticket.priority === 'medium' ? 'text-yellow-800' : 'text-green-800'
                            }`}>
                            {ticket.priority === 'low' ? 'BAJA' :
                                ticket.priority === 'medium' ? 'MEDIA' : 'ALTA'}
                        </Text>
                    </View>

                    {ticket.category && (
                        <View className="bg-gray-50 px-2 py-0.5 rounded">
                            <Text className="text-xs text-gray-500" numberOfLines={1}>{ticket.category.name}</Text>
                        </View>
                    )}
                </View>
                <View className={`px-2 py-0.5 rounded-full ${getStatusColor(ticket.status).split(' ')[0]}`}>
                    <Text className={`text-xs font-bold ${getStatusColor(ticket.status).split(' ')[1]}`}>
                        {getStatusLabel(ticket.status)}
                    </Text>
                </View>
            </View>

            <Text className="font-bold text-lg text-gray-900 mb-2" numberOfLines={2}>
                {ticket.title}
            </Text>

            <View className="flex-row items-center justify-between mt-2">
                <View className="flex-row items-center">
                    {ticket.company ? (
                        <>
                            {ticket.company.logoUrl ? (
                                <Avatar.Image size={24} source={{ uri: ticket.company.logoUrl }} />
                            ) : (
                                <Avatar.Text size={24} label={ticket.company.name?.substring(0, 2) || 'NA'} />
                            )}
                            <View className="ml-2">
                                <Text className="text-gray-600 text-xs font-medium">{ticket.company.name || 'Sin Empresa'}</Text>
                                {ticket.area && (
                                    <Text className="text-gray-400 text-[10px]">{ticket.area.name}</Text>
                                )}
                            </View>
                        </>
                    ) : null}
                </View>

                <Text className="text-gray-400 text-xs">
                    {ticket.createdAt ? formatDistanceToNow(new Date(ticket.createdAt), { addSuffix: true, locale: es }) : '-'}
                </Text>
            </View>
        </TouchableOpacity>
    );
}
