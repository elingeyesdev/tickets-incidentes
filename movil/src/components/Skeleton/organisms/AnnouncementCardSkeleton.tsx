import React from 'react';
import { View, StyleProp, ViewStyle } from 'react-native';
import { SkeletonBox } from '../atoms/SkeletonBox';
import { SkeletonText } from '../atoms/SkeletonText';
import { SkeletonCircle } from '../atoms/SkeletonCircle';

export interface AnnouncementCardSkeletonProps {
    className?: string;
    style?: StyleProp<ViewStyle>;
}

export const AnnouncementCardSkeleton: React.FC<AnnouncementCardSkeletonProps> = ({
    className,
    style,
}) => {
    return (
        <View
            className={`bg-white rounded-xl mb-3 shadow-sm border border-gray-100 overflow-hidden ${className || ''}`}
            style={style}
        >
            {/* Header Strip */}
            <View className="flex-row items-center px-3 py-2 bg-gray-50 border-l-4 border-gray-200">
                <SkeletonCircle size={16} className="mr-2" />
                <SkeletonBox width={80} height={12} />
                <View className="flex-1" />
                <SkeletonBox width={50} height={16} borderRadius={4} />
            </View>

            <View className="p-3">
                {/* Title */}
                <SkeletonBox width="90%" height={18} className="mb-2" />

                {/* Excerpt */}
                <SkeletonText lines={2} lineHeight={14} gap={4} className="mb-3" />

                {/* Footer */}
                <View className="flex-row justify-between items-center pt-2 border-t border-gray-50">
                    <View className="flex-row items-center">
                        <SkeletonBox width={12} height={12} borderRadius={6} className="mr-1" />
                        <SkeletonBox width={60} height={12} />
                    </View>
                    <SkeletonBox width={50} height={10} />
                </View>
            </View>
        </View>
    );
};
