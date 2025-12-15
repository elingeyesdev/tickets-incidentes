import React from 'react';
import { View, StyleProp, ViewStyle } from 'react-native';
import { SkeletonBox } from '../atoms/SkeletonBox';
import { SkeletonText } from '../atoms/SkeletonText';
import { SkeletonCircle } from '../atoms/SkeletonCircle';

export interface TicketCardSkeletonProps {
    className?: string;
    style?: StyleProp<ViewStyle>;
}

export const TicketCardSkeleton: React.FC<TicketCardSkeletonProps> = ({
    className,
    style,
}) => {
    return (
        <View
            className={`bg-white rounded-xl mb-3 shadow-sm border border-gray-100 overflow-hidden ${className || ''}`}
            style={style}
        >
            <View className="flex-row">
                {/* Status Strip */}
                <SkeletonBox width={4} height="100%" borderRadius={0} />

                <View className="flex-1 p-3">
                    {/* Header: Code + Status */}
                    <View className="flex-row justify-between items-center mb-2">
                        <SkeletonBox width={80} height={14} />
                        <SkeletonBox width={60} height={20} borderRadius={10} />
                    </View>

                    {/* Title */}
                    <SkeletonText lines={2} lineHeight={16} gap={4} className="mb-3" />

                    {/* Footer: Company + Date + Agent */}
                    <View className="flex-row justify-between items-center pt-2 border-t border-gray-50">
                        <View>
                            <View className="flex-row items-center mb-1">
                                <SkeletonBox width={12} height={12} borderRadius={6} className="mr-1" />
                                <SkeletonBox width={80} height={12} />
                            </View>
                            <SkeletonBox width={60} height={10} />
                        </View>

                        <SkeletonCircle size={24} />
                    </View>
                </View>
            </View>
        </View>
    );
};
