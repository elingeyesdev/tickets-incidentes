import { View, Text } from 'react-native';
import Animated, { FadeIn, ZoomIn } from 'react-native-reanimated';

export function Logo({ size = 'md' }: { size?: 'sm' | 'md' | 'lg' }) {
    const sizeClasses = {
        sm: 'text-2xl',
        md: 'text-4xl',
        lg: 'text-6xl',
    };

    return (
        <Animated.View entering={ZoomIn.duration(1000)} className="items-center">
            <View className="bg-blue-600 rounded-2xl p-4 shadow-lg">
                <Text className={`font-bold text-white ${sizeClasses[size]}`}>HD</Text>
            </View>
            <Animated.Text entering={FadeIn.delay(500).duration(1000)} className="mt-4 text-2xl font-bold text-gray-800">
                Helpdesk
            </Animated.Text>
        </Animated.View>
    );
}
