import React from 'react';
import { View, Text } from 'react-native';
import { MaterialCommunityIcons } from '@expo/vector-icons';

interface BusinessHoursProps {
    hours: Record<string, { open: string; close: string }>;
    timezone: string;
}

const dayNames: Record<string, string> = {
    monday: 'Lunes',
    tuesday: 'Martes',
    wednesday: 'Miércoles',
    thursday: 'Jueves',
    friday: 'Viernes',
    saturday: 'Sábado',
    sunday: 'Domingo',
};

const dayOrder = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

export function BusinessHours({ hours, timezone }: BusinessHoursProps) {
    const getStatus = () => {
        // Simple logic for now, assuming local time matches or ignoring timezone complexity for MVP
        // In a real app, we'd use date-fns-tz to handle timezone correctly
        const now = new Date();
        const days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        const currentDay = days[now.getDay()];

        const todayHours = hours[currentDay];

        if (!todayHours) return { isOpen: false, text: 'Cerrado hoy' };

        const currentTime = `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}`;

        if (currentTime >= todayHours.open && currentTime <= todayHours.close) {
            return { isOpen: true, text: 'Abierto ahora' };
        }

        return { isOpen: false, text: `Cerrado • Abre ${todayHours.open}` };
    };

    const status = getStatus();

    return (
        <View className="p-6 bg-white mt-2">
            <Text className="text-lg font-bold text-gray-900 mb-4">Horarios de Atención</Text>

            {/* Status Indicator */}
            <View className={`flex-row items-center p-3 rounded-lg mb-4 ${status.isOpen ? 'bg-green-50' : 'bg-red-50'}`}>
                <MaterialCommunityIcons
                    name={status.isOpen ? "clock-check-outline" : "clock-remove-outline"}
                    size={20}
                    color={status.isOpen ? "#16A34A" : "#DC2626"}
                />
                <Text className={`ml-2 font-medium ${status.isOpen ? 'text-green-700' : 'text-red-700'}`}>
                    {status.text}
                </Text>
            </View>

            {/* Hours List */}
            {dayOrder.map((day) => {
                const dayHours = hours[day];
                const isToday = new Date().getDay() === (dayOrder.indexOf(day) + 1) % 7;

                return (
                    <View key={day} className={`flex-row justify-between py-2 border-b border-gray-100 ${isToday ? 'bg-blue-50 px-2 -mx-2 rounded' : ''}`}>
                        <Text className={`capitalize ${isToday ? 'font-bold text-blue-900' : 'text-gray-600'}`}>
                            {dayNames[day]}
                        </Text>
                        <Text className={`font-medium ${isToday ? 'text-blue-900' : 'text-gray-900'}`}>
                            {dayHours ? `${dayHours.open} - ${dayHours.close}` : 'Cerrado'}
                        </Text>
                    </View>
                );
            })}

            <Text className="text-xs text-gray-400 mt-4">
                Zona horaria: {timezone}
            </Text>
        </View>
    );
}
