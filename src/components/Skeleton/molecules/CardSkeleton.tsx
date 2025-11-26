import React from 'react';
import { View, StyleProp, ViewStyle } from 'react-native';
import { SkeletonBox } from '../atoms/SkeletonBox';
import { SkeletonText } from '../atoms/SkeletonText';
import { SkeletonCircle } from '../atoms/SkeletonCircle';

export interface CardSkeletonProps {
    withHeader?: boolean;
    withFooter?: boolean;
    lines?: number;
    className?: string;
    style?: StyleProp<ViewStyle>;
}

export const CardSkeleton: React.FC<CardSkeletonProps> = ({
    withHeader = true,
    withFooter = true,
    lines = 3,
    className,
    style,
}) => {
    return (
        <View
            className={`bg-white rounded-xl p-4 mb-4 shadow-sm border border-gray-100 ${className || ''}`}
            style={style}
        >
            {withHeader && (
                <View className="flex-row items-center mb-3">
                    <SkeletonCircle size={24} className="mr-2" />
                    <SkeletonBox width={100} height={16} />
                    <View className="flex-1" />
                    <SkeletonBox width={60} height={20} borderRadius={10} />
                </View>
            )}

            <SkeletonText lines={lines} gap={8} />

            {withFooter && (
                <View className="flex-row items-center justify-between mt-4 pt-3 border-t border-gray-50">
                    <View className="flex-row items-center">
                        <SkeletonCircle size={16} className="mr-2" />
                        <SkeletonBox width={80} height={12} />
                    </View>
                    <SkeletonBox width={60} height={12} />
                </View>
            )}
        </View>
    );
};
