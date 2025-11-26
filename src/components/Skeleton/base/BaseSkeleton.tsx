import React, { useEffect } from 'react';
import { ViewStyle, StyleProp } from 'react-native';
import Animated, {
    useSharedValue,
    useAnimatedStyle,
    withRepeat,
    withTiming,
    withSequence,
    Easing,
} from 'react-native-reanimated';
import { useTheme } from 'react-native-paper';

interface BaseSkeletonProps {
    style?: StyleProp<ViewStyle>;
    className?: string;
    children?: React.ReactNode;
}

export const BaseSkeleton: React.FC<BaseSkeletonProps> = ({ style, className, children }) => {
    const theme = useTheme();
    const opacity = useSharedValue(0.3);

    useEffect(() => {
        opacity.value = withRepeat(
            withSequence(
                withTiming(0.7, { duration: 1000, easing: Easing.inOut(Easing.ease) }),
                withTiming(0.3, { duration: 1000, easing: Easing.inOut(Easing.ease) })
            ),
            -1, // Infinite loop
            true // Reverse (though sequence handles it, this makes it smoother)
        );
    }, []);

    const animatedStyle = useAnimatedStyle(() => {
        return {
            opacity: opacity.value,
        };
    });

    // Default background color based on theme (light gray for light mode, dark gray for dark mode)
    // We can override this via className or style
    const backgroundColor = theme.dark ? '#374151' : '#E5E7EB'; // gray-700 : gray-200

    return (
        <Animated.View
            style={[
                { backgroundColor },
                style,
                animatedStyle,
            ]}
            className={className}
        >
            {children}
        </Animated.View>
    );
};
